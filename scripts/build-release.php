<?php

/*
| Builds the FTP-ready release package (developer machine only).
|
|   php scripts/build-release.php            -> dist/biofy-tr-<version>.zip
|   php scripts/build-release.php --no-npm   (reuse the existing public/build)
|
| Steps: compile assets -> copy runtime files to a staging folder -> composer install
| --no-dev -> add web-access guards -> SHA-256 manifest signed with Ed25519 -> zip.
| The signing key is kept OUTSIDE the repository (BIOFY_SIGNING_KEY or ~/.biofy-devtools).
*/

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__);
// config/biofy.php uses framework helpers, so the version is read without booting Laravel.
preg_match("/'version' => '([^']+)'/", file_get_contents($root.'/config/biofy.php'), $m);
$version = $m[1] ?? exit("Version not found in config/biofy.php\n");

$args = array_slice($argv, 1);
$name = 'biofy-tr-'.$version;
$dist = $root.'/dist';
$stage = $dist.'/'.$name;

function step(string $message): void
{
    fwrite(STDOUT, "\n==> {$message}\n");
}

function run(string $command, string $cwd): void
{
    fwrite(STDOUT, "    $ {$command}\n");
    $process = proc_open($command, [1 => STDOUT, 2 => STDERR], $pipes, $cwd);

    if (! is_resource($process) || proc_close($process) !== 0) {
        fwrite(STDERR, "Command failed: {$command}\n");
        exit(1);
    }
}

function rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($items as $item) {
        $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

/** Copies $src into $dst skipping paths that match any regex in $exclude (relative, forward slashes). */
function copyTree(string $src, string $dst, array $exclude = []): int
{
    $count = 0;
    $src = rtrim(str_replace('\\', '/', $src), '/');

    if (is_file($src)) {
        @mkdir(dirname($dst), 0775, true);
        copy($src, $dst);

        return 1;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $item) {
        $relative = substr(str_replace('\\', '/', $item->getPathname()), strlen($src) + 1);

        foreach ($exclude as $pattern) {
            if (preg_match($pattern, $relative)) {
                continue 2;
            }
        }

        $target = $dst.'/'.$relative;

        if ($item->isDir()) {
            @mkdir($target, 0775, true);
        } else {
            @mkdir(dirname($target), 0775, true);
            copy($item->getPathname(), $target);
            $count++;
        }
    }

    return $count;
}

step("Building Biofy.TR {$version}");

if (! in_array('--no-npm', $args, true)) {
    step('Compiling front-end assets');
    run('npm ci --no-audit --no-fund', $root);
    run('node scripts/build-icons.mjs', $root);
    run('npm run build', $root);
}

if (! is_file($root.'/public/build/manifest.json')) {
    fwrite(STDERR, "public/build/manifest.json is missing; run npm run build first.\n");
    exit(1);
}

step('Preparing staging folder');
rrmdir($stage);
@mkdir($stage, 0775, true);

$copy = [
    'app' => [],
    'bootstrap' => ['#^cache/.+\.php$#', '#^public-path\.php$#'],
    'config' => [],
    'database' => ['#\.sqlite$#', '#^factories/#'],
    'install' => [],
    'lang' => [],
    'public' => ['#^hot$#', '#^storage$#', '#^media/(?!\.htaccess$).+#', '#^fonts-manifest\.dev\.json$#'],
    'resources' => ['#^(css|js)/#'],
    'routes' => [],
    'docs' => [],
    'artisan' => [],
    'composer.json' => [],
    'composer.lock' => [],
    '.env.example' => [],
    '.htaccess' => [],
    'README.md' => [],
    'CHANGELOG.md' => [],
];

$files = 0;
foreach ($copy as $path => $exclude) {
    if (file_exists($root.'/'.$path)) {
        $files += copyTree($root.'/'.$path, $stage.'/'.$path, $exclude);
    }
}

// Empty runtime folders (kept by .gitignore placeholders) and access guards.
foreach (['storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
    @mkdir($stage.'/'.$dir, 0775, true);
    file_put_contents($stage.'/'.$dir.'/.gitignore', "*\n!.gitignore\n");
}

step('Installing production dependencies (composer install --no-dev)');
$composer = getenv('COMPOSER_BIN') ?: 'composer';
run($composer.' install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts', $stage);
run(PHP_BINARY.' artisan package:discover --ansi', $stage);
@unlink($stage.'/bootstrap/cache/packages.php') || true;
@unlink($stage.'/bootstrap/cache/services.php') || true;

$deny = "# Application files: never served over the web.\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
foreach (['app', 'bootstrap', 'config', 'database', 'install', 'lang', 'resources', 'routes', 'storage', 'vendor', 'docs'] as $dir) {
    file_put_contents($stage.'/'.$dir.'/.htaccess', $deny);
}

step('Signing release manifest');
$manifest = ['name' => 'biofy-tr', 'version' => $version, 'built_at' => gmdate('c'), 'files' => []];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    $relative = substr(str_replace('\\', '/', $file->getPathname()), strlen(str_replace('\\', '/', $stage)) + 1);

    // Runtime-written files are not part of the integrity check.
    if (preg_match('#^(storage/|bootstrap/cache/|public/media/(?!\.htaccess$)|\.env$|release-)#', $relative)) {
        continue;
    }

    $manifest['files'][$relative] = hash_file('sha256', $file->getPathname());
}
ksort($manifest['files']);
$manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($stage.'/release-manifest.json', $manifestJson);

$keyFile = getenv('BIOFY_SIGNING_KEY') ?: (getenv('USERPROFILE') ?: getenv('HOME')).'/.biofy-devtools/release-signing.key';
if (! is_file($keyFile)) {
    $pair = sodium_crypto_sign_keypair();
    @mkdir(dirname($keyFile), 0700, true);
    file_put_contents($keyFile, base64_encode($pair));
    fwrite(STDOUT, "    Created a new signing key: {$keyFile} (keep it secret, back it up)\n");
}
$pair = base64_decode(trim(file_get_contents($keyFile)));
$secret = sodium_crypto_sign_secretkey($pair);
$public = base64_encode(sodium_crypto_sign_publickey($pair));
file_put_contents($stage.'/release-manifest.sig', base64_encode(sodium_crypto_sign_detached($manifestJson, $secret)));
file_put_contents($stage.'/release-public-key.txt', $public."\n");
fwrite(STDOUT, '    Files in manifest: '.count($manifest['files'])."\n    Public key: {$public}\n");

step('Safety checks');
foreach (['.env', 'database/database.sqlite', 'storage/logs/laravel.log', 'node_modules', 'tests', 'vendor/phpunit', 'vendor/fakerphp'] as $forbidden) {
    if (file_exists($stage.'/'.$forbidden)) {
        fwrite(STDERR, "Refusing to package: {$forbidden}\n");
        exit(1);
    }
}

step('Creating zip archive');
$zipPath = $dist.'/'.$name.'.zip';
@unlink($zipPath);
$zip = new ZipArchive;
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($iterator as $item) {
    $relative = $name.'/'.substr(str_replace('\\', '/', $item->getPathname()), strlen(str_replace('\\', '/', $stage)) + 1);
    $item->isDir() ? $zip->addEmptyDir($relative) : $zip->addFile($item->getPathname(), $relative);
}
$zip->close();

$hash = hash_file('sha256', $zipPath);
file_put_contents($zipPath.'.sha256', $hash.'  '.basename($zipPath)."\n");

step('Done');
fwrite(STDOUT, sprintf("    %s (%.1f MB)\n    sha256 %s\n", $zipPath, filesize($zipPath) / 1048576, $hash));
