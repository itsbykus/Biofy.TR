<?php

namespace Tests\Unit;

use BiofyInstaller\Database;
use BiofyInstaller\EnvWriter;
use BiofyInstaller\Requirements;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;
use ZipArchive;

/** Plain-PHP pieces of the web installer (install/) and the release verifier (scripts/). */
class InstallerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        require_once base_path('install/Requirements.php');
        require_once base_path('install/Database.php');
        require_once base_path('install/EnvWriter.php');

        $this->dir = sys_get_temp_dir().'/biofy-installer-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->dir.'/app/public');
        File::ensureDirectoryExists($this->dir.'/app/config');
        file_put_contents($this->dir.'/app/config/biofy.php', "<?php return ['version' => '9.8.7'];");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function requirements(?string $public = null): Requirements
    {
        return new Requirements($this->dir.'/app', $public ?? $this->dir.'/app/public');
    }

    private function check(Requirements $requirements, string $key): ?array
    {
        return collect($requirements->all())->firstWhere('key', $key);
    }

    private function file(string $path, string $content = 'x'): void
    {
        File::ensureDirectoryExists(dirname($this->dir.'/'.$path));
        file_put_contents($this->dir.'/'.$path, $content);
    }

    public function test_source_archive_without_vendor_and_build_is_reported(): void
    {
        $requirements = $this->requirements();

        $this->assertTrue($requirements->isSourceArchive());
        $this->assertFalse($this->check($requirements, 'vendor')['ok']);
        $this->assertFalse($this->check($requirements, 'assets')['ok']);
        $this->assertFalse($requirements->passes());
        $this->assertSame('9.8.7', $requirements->version());
        $this->assertNull($this->check($requirements, 'complete'), 'no release manifest: no completeness check');
    }

    public function test_vendor_and_every_vite_asset_must_exist(): void
    {
        $this->file('app/vendor/autoload.php');
        $this->file('app/vendor/laravel/framework/src/Illuminate/Foundation/Application.php');
        $this->file('app/public/build/manifest.json', json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-1.js', 'css' => ['assets/app-2.css']],
            'resources/fonts/a.woff2' => ['file' => 'assets/a-3.woff2'],
        ]));
        $this->file('app/public/build/assets/app-1.js');
        $this->file('app/public/build/assets/a-3.woff2');

        $requirements = $this->requirements();
        $this->assertTrue($this->check($requirements, 'vendor')['ok']);
        $this->assertFalse($this->check($requirements, 'assets')['ok'], 'the CSS file is missing');
        $this->assertFalse($requirements->isSourceArchive());

        $this->file('app/public/build/assets/app-2.css');
        $this->assertTrue($this->check($this->requirements(), 'assets')['ok']);
    }

    public function test_incomplete_upload_is_detected_from_the_release_manifest(): void
    {
        $this->file('app/release-manifest.json', json_encode(['files' => [
            'app/Models/User.php' => 'h', 'vendor/.htaccess' => 'h', 'vendor/pkg/.editorconfig' => 'h', 'public/index.php' => 'h',
        ]]));
        $this->file('app/app/Models/User.php');
        // Layout B: public/ files live in another folder (the subdomain's document root).
        $this->file('docroot/index.php');

        $requirements = $this->requirements($this->dir.'/docroot');
        $this->assertSame(['vendor/.htaccess'], $requirements->missingPackageFiles(), '.htaccess counts, other dot-files are skipped');
        $this->assertFalse($this->check($requirements, 'complete')['ok']);
        $this->assertStringContainsString('vendor/.htaccess', $this->check($requirements, 'complete')['detail']);

        $this->file('app/vendor/.htaccess');
        $this->assertTrue($this->check($this->requirements($this->dir.'/docroot'), 'complete')['ok']);
    }

    public function test_env_file_is_production_ready_and_safely_quoted(): void
    {
        $key = EnvWriter::generateKey();
        $this->assertMatchesRegularExpression('/^base64:[A-Za-z0-9+\/]{43}=$/', $key);
        $this->assertSame(32, strlen(base64_decode(substr($key, 7))));
        $this->assertNotSame($key, EnvWriter::generateKey());

        $env = EnvWriter::build([
            'APP_NAME' => 'My "Bio" $HOME', 'APP_KEY' => $key, 'APP_URL' => 'https://bio.example.com/', 'APP_LOCALE' => 'tr',
            'DB_HOST' => 'localhost', 'DB_PORT' => 3306, 'DB_DATABASE' => 'db', 'DB_USERNAME' => 'user',
            'DB_PASSWORD' => "p\"a\$s\nAPP_DEBUG=true", 'DB_PREFIX' => '',
        ]);

        // Read back with the parser Laravel itself uses.
        $values = \Dotenv\Dotenv::parse($env);
        $this->assertSame('production', $values['APP_ENV']);
        $this->assertSame('false', $values['APP_DEBUG']);
        $this->assertSame($key, $values['APP_KEY']);
        $this->assertSame('https://bio.example.com', $values['APP_URL']);
        $this->assertSame('true', $values['SESSION_SECURE_COOKIE']);
        $this->assertSame('My "Bio" $HOME', $values['APP_NAME'], 'no variable expansion');
        $this->assertSame('p"a$sAPP_DEBUG=true', $values['DB_PASSWORD'], 'a line break in a value cannot inject a variable');
        $this->assertSame(1, preg_match_all('/^APP_DEBUG=/m', $env));

        // A retry after an interrupted attempt keeps the key the seeders already encrypted with.
        $this->assertSame($key, EnvWriter::existingKey($env));
        $this->assertSame($key, EnvWriter::existingKey("APP_NAME=x\nAPP_KEY={$key}\n"));
        $this->assertNull(EnvWriter::existingKey("APP_KEY=\n"));
        $this->assertNull(EnvWriter::existingKey("APP_KEY=base64:short=\n"));
        $this->assertNull(EnvWriter::existingKey("APP_KEY=\"{$key}'\n"));
    }

    public function test_only_an_interrupted_attempt_without_accounts_can_be_resumed(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $call =fn (array $tables) => (new \ReflectionMethod(Database::class, 'resumable'))->invoke(null, $pdo, 'bio_', $tables);

        $this->assertFalse($call(['bio_options', 'bio_posts']), 'another application');
        $this->assertTrue($call(['bio_migrations', 'bio_cache']), 'stopped before the users table');

        $pdo->exec('CREATE TABLE bio_users (id INTEGER)');
        $this->assertTrue($call(['bio_migrations', 'bio_users']), 'schema created, no account yet');

        $pdo->exec('INSERT INTO bio_users VALUES (1)');
        $this->assertFalse($call(['bio_migrations', 'bio_users']), 'a finished installation');
    }

    public function test_release_verifier_rejects_a_source_code_archive(): void
    {
        $zipPath = $this->dir.'/Biofy.TR-9.8.7.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach (['config/biofy.php' => "'version' => '9.8.7'", 'package.json' => '{}', 'vite.config.js' => '', 'public/index.php' => '<?php'] as $path => $content) {
            $zip->addFromString('Biofy.TR-9.8.7/'.$path, $content);
        }
        $zip->close();

        exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('scripts/verify-release.php')).' '.escapeshellarg($zipPath).' 2>&1', $output, $code);
        $output = implode("\n", $output);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('FAIL  this is a source code archive', $output);
        $this->assertStringContainsString('vendor/autoload.php', $output);
        $this->assertStringContainsString('public/build/manifest.json', $output);
        $this->assertStringContainsString('must not be published or installed', $output);
    }
}
