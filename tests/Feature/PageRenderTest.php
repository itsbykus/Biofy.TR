<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\CustomDomain;
use App\Models\FeatureDefinition;
use App\Models\Package;
use App\Models\Page;
use App\Services\BlockService;
use App\Services\SocialLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Every screen renders with realistic data, in both languages (catches view/translation errors). */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_public_site_pages_render(): void
    {
        foreach (['tr', 'en'] as $locale) {
            $this->withSession(['locale' => $locale]);

            foreach (['/', '/features', '/themes', '/pricing', '/contact', '/about', '/help', '/privacy', '/terms', '/cookies', '/kvkk', '/login', '/register', '/forgot-password', '/robots.txt', '/sitemap.xml', '/sitemap-pages.xml', '/sitemap-profiles-1.xml'] as $path) {
                $this->get($path)->assertOk();
            }
        }

        $this->withSession(['locale' => 'tr'])->get('/pricing')->assertSee('Daha fazlasına mı ihtiyacınız var? Bizimle irtibata geçiniz.');
        $this->get('/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_user_dashboard_pages_render(): void
    {
        $user = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($user, ['slug' => 'renderuser']);
        $blocks = app(BlockService::class);
        $blocks->create($profile, 'link', ['data' => ['url' => 'https://example.com', 'title' => 'Site']]);
        $blocks->create($profile, 'faq', ['data' => ['items' => [['q' => 'Q?', 'a' => 'A.']]]]);
        $blocks->create($profile, 'product', ['data' => ['items' => [['title' => 'Item', 'price' => '10 ₺', 'url' => 'https://shop.example.com']]]]);
        app(SocialLinkService::class)->create($profile, 'instagram', '@renderuser');
        CustomDomain::query()->create(['user_id' => $user->id, 'profile_id' => $profile->id, 'hostname' => 'links.example.org', 'verification_token' => str_repeat('a', 32), 'status' => 'pending']);

        foreach (['tr', 'en'] as $locale) {
            $this->actingAs($user)->withSession(['locale' => $locale]);

            foreach (['dashboard', 'pages.index', 'pages.create', 'links.index', 'dashboard.themes', 'analytics.index', 'domains.index', 'media.index', 'account.edit', 'security.index', 'api-tokens.index', 'plan.show', 'dashboard.help'] as $route) {
                $this->get(route($route))->assertOk();
            }

            foreach (['content', 'profile', 'social', 'theme', 'seo', 'settings'] as $tab) {
                $this->get(route('pages.edit', ['profile' => $profile, 'tab' => $tab]))->assertOk();
            }

            foreach (array_keys(app(\App\Blocks\BlockRegistry::class)->all()) as $type) {
                $this->get(route('blocks.create', ['profile' => $profile, 'type' => $type]))->assertOk();
            }

            $this->get(route('pages.preview', $profile))->assertOk();
            $this->get(route('pages.qr', ['profile' => $profile, 'format' => 'svg']))->assertOk();
            $this->get(route('pages.qr', ['profile' => $profile, 'format' => 'png']))->assertOk()->assertHeader('Content-Type', 'image/png');
        }
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $this->makeProfile($user, publish: true);
        $message = ContactMessage::query()->create(['name' => 'A', 'email' => 'a@example.com', 'subject' => 'Hi', 'message' => 'Hello there', 'status' => 'new']);

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);

        foreach (['tr', 'en'] as $locale) {
            $this->withSession(['locale' => $locale, 'auth.password_confirmed_at' => time()]);

            foreach (['admin.dashboard', 'admin.users.index', 'admin.profiles.index', 'admin.packages.index', 'admin.packages.create', 'admin.features.index', 'admin.themes.index', 'admin.pages.index', 'admin.pages.create', 'admin.menus.edit', 'admin.templates.index', 'admin.reserved.index', 'admin.announcement.create', 'admin.media.index', 'admin.domains.index', 'admin.messages.index', 'admin.audit.index', 'admin.security-events.index', 'admin.backups.index', 'admin.system.index'] as $route) {
                $this->get(route($route))->assertOk();
            }

            $this->get(route('admin.users.show', $user))->assertOk();
            $this->get(route('admin.packages.edit', Package::query()->first()))->assertOk();
            $this->get(route('admin.pages.edit', Page::query()->first()))->assertOk();
            $this->get(route('admin.messages.show', $message))->assertOk();
            $this->get(route('admin.templates.edit', ['welcome', $locale]))->assertOk();

            foreach (array_keys(\App\Http\Controllers\Admin\SettingsController::groups()) as $group) {
                $this->get(route('admin.settings.edit', $group))->assertOk();
            }
        }

        $this->assertGreaterThan(0, FeatureDefinition::query()->count());
    }

    public function test_error_pages_render(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee(__('errors.404_title'));
        $this->get('/admin')->assertRedirect(route('login'));
    }
}
