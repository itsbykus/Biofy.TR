<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Services\BlockService;
use App\Services\ProfileService;
use App\Services\Settings;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        foreach (['avatar', 'block', 'banner', 'gallery', 'seo', 'brand', 'favicon'] as $dir) {
            File::deleteDirectory(public_path('media/'.$dir));
        }

        parent::tearDown();
    }

    public function test_stored_xss_is_escaped_on_the_public_page(): void
    {
        $user = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($user, ['slug' => 'xsstest']);
        $payload = '<script>alert(1)</script><img src=x onerror=alert(1)>';
        app(ProfileService::class)->updateDetails($profile, ['display_name' => $payload, 'bio_short' => $payload, 'title' => '"><svg onload=alert(1)>']);
        app(BlockService::class)->create($profile->fresh(), 'text', ['data' => ['text' => $payload]]);
        app(BlockService::class)->create($profile->fresh(), 'link', ['data' => ['url' => 'https://ok.example.com', 'title' => $payload]]);
        app(BlockService::class)->create($profile->fresh(), 'faq', ['data' => ['items' => [['q' => $payload, 'a' => $payload]]]]);
        app(ProfileService::class)->publish($profile->fresh(), $user);

        $response = $this->get('/xsstest')->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringNotContainsString('<svg onload', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringContainsString('https://www.youtube-nocookie.com', $csp);
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_reflected_input_is_escaped(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin)->get(route('admin.users.index', ['q' => '"><script>alert(1)</script>']))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_sql_injection_in_search_is_harmless(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->makeUser(['name' => 'Innocent']);

        $this->actingAs($admin)->get(route('admin.users.index', ['q' => "' OR '1'='1"]))->assertOk()->assertDontSee('Innocent');
        $this->actingAs($admin)->get(route('admin.users.index', ['q' => "x'); DROP TABLE users; --"]))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index', ['sort' => 'id; DROP TABLE users']))->assertOk();
        $this->assertDatabaseCount('users', 2);
    }

    public function test_csrf_protection_is_enforced(): void
    {
        // Laravel skips CSRF checks in unit tests; use the real middleware behaviour here.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $user = $this->makeUser(['email' => 'csrf@example.com']);

        $this->post(route('login.store'), ['email' => 'csrf@example.com', 'password' => 'Password123!'])->assertStatus(302)->assertSessionHas('error', __('errors.session_expired'));
        $this->assertGuest();

        $this->actingAs($user)->put(route('account.update'), ['name' => 'Changed'])->assertSessionHas('error', __('errors.session_expired'));
        $this->assertNotSame('Changed', $user->fresh()->name);
    }

    /** A real temporary file (Laravel's fake files use tmpfile(), which is locked on Windows). */
    private function upload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bft');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function pngBytes(int $w, int $h): string
    {
        $image = imagecreatetruecolor($w, $h);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 207, 0));
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    public function test_upload_attacks_are_rejected_and_images_reencoded(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        // PHP code disguised as an image.
        $this->postJson(route('media.store'), ['file' => $this->upload('shell.jpg', "<?php system(\$_GET['c']); ?>"), 'purpose' => 'block'])->assertStatus(422);
        $this->postJson(route('media.store'), ['file' => $this->upload('shell.php.png', "\x89PNG<?php system(\$_GET['c']); ?>"), 'purpose' => 'block'])->assertStatus(422);

        // SVG (can carry scripts) is not accepted.
        $this->postJson(route('media.store'), ['file' => $this->upload('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'purpose' => 'block'])->assertStatus(422);

        // A real image with appended PHP is re-encoded: the payload does not survive.
        $png = $this->upload('photo.png', $this->pngBytes(300, 200).'<?php echo "pwned"; ?>');
        $id = $this->postJson(route('media.store'), ['file' => $png, 'purpose' => 'avatar'])->assertCreated()->json('id');

        $media = MediaFile::query()->findOrFail($id);
        $stored = file_get_contents(public_path('media/'.$media->path));
        $this->assertStringNotContainsString('<?php', $stored);
        $this->assertMatchesRegularExpression('#^avatar/\d{4}/\d{2}/[a-z0-9]{32}\.(webp|png)$#', $media->path);
        $this->assertSame(512, $media->width, 'avatar is cropped to the target size');

        // Invalid purpose is refused.
        $this->postJson(route('media.store'), ['file' => $this->upload('a.png', $this->pngBytes(10, 10)), 'purpose' => '../../etc'])->assertStatus(422);
    }

    public function test_storage_quota_is_enforced(): void
    {
        $user = $this->makeUser();
        app(\App\Services\UserAccountService::class)->setOverride($user, 'storage.quota_mb', '0', null, null, $this->makeSuperAdmin());

        $this->actingAs($user->fresh())->postJson(route('media.store'), ['file' => $this->upload('a.png', $this->pngBytes(50, 50)), 'purpose' => 'block'])
            ->assertStatus(422)->assertJson(['message' => __('media.quota_exceeded')]);
    }

    public function test_other_users_media_cannot_be_deleted(): void
    {
        $owner = $this->makeUser();
        $media = MediaFile::query()->create(['user_id' => $owner->id, 'path' => 'block/2026/01/aaaaaaaa.webp', 'mime' => 'image/webp', 'size' => 1, 'sha256' => str_repeat('0', 64)]);

        $this->actingAs($this->makeUser())->deleteJson(route('media.destroy', $media))->assertNotFound();
        $this->assertNotNull($media->fresh());
    }

    public function test_no_open_redirect_through_language_switch(): void
    {
        $this->withHeader('Referer', 'https://evil.example.com/phish')->get('/locale/en')->assertRedirect(route('home'));
        $this->get('/locale/xx')->assertNotFound();
    }

    public function test_sensitive_paths_are_protected_by_htaccess_rules(): void
    {
        $root = file_get_contents(base_path('.htaccess'));
        $this->assertStringContainsString('RewriteRule ^(.*)$ public/$1', $root);

        foreach (['app', 'bootstrap', 'config', 'database', 'install', 'lang', 'resources', 'routes', 'storage', 'tests'] as $dir) {
            $this->assertFileExists(base_path($dir.'/.htaccess'), "{$dir}/.htaccess must deny web access");
            $this->assertStringContainsString('Require all denied', file_get_contents(base_path($dir.'/.htaccess')));
        }

        // vendor/ (installed by Composer, not versioned) and docs/ get their guard while the release is packaged.
        $this->assertMatchesRegularExpression("/foreach \(\[[^\]]*'vendor'[^\]]*'docs'\] as \\\$dir\)/", file_get_contents(base_path('scripts/build-release.php')));

        $public = file_get_contents(public_path('.htaccess'));
        $this->assertStringContainsString('Options -Indexes', $public);
        $media = file_get_contents(public_path('media/.htaccess'));
        $this->assertStringContainsString('php_flag engine off', $media);

        // Nothing sensitive is placed under the web root.
        $this->assertFileDoesNotExist(public_path('.env'));
        $this->assertFileDoesNotExist(public_path('storage'));
    }

    public function test_maintenance_mode_and_staff_bypass(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, ['slug' => 'stillonline'], publish: true);
        app(Settings::class)->set('maintenance.enabled', true);

        $this->get('/')->assertStatus(503);
        $this->get(route('login'))->assertOk();
        $this->get('/stillonline')->assertOk();
        $this->actingAs($this->makeSuperAdmin())->get('/')->assertOk();

        app(Settings::class)->set('maintenance.keep_profiles_online', false);
        $this->app['auth']->forgetGuards();
        $this->get('/stillonline')->assertStatus(503);
    }

    public function test_cron_endpoint_requires_the_secret_token(): void
    {
        $this->get('/cron/wrong-token')->assertNotFound();
        $token = app(Settings::class)->get('cron.token');
        $this->assertNotEmpty($token);
        $this->get('/cron/'.$token)->assertOk()->assertJsonPath('ok', true);
        $this->assertNotNull(app(Settings::class)->get('cron.last_run_at'));
    }

    public function test_debug_is_off_in_production_config(): void
    {
        $env = file_get_contents(base_path('.env.example'));
        $this->assertStringContainsString('APP_DEBUG=false', $env);
        $this->assertStringContainsString('APP_ENV=production', $env);
    }
}
