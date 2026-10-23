<?php

namespace Tests\Feature;

use App\Services\Settings;
use App\Services\SocialLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin → Settings → Home page (landing) and Footer, and the logo size, as visitors see them. */
class SiteCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function save(string $group, array $fields): void
    {
        $this->actingAs($this->makeSuperAdmin())->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', $group), $fields)->assertSessionHasNoErrors()->assertRedirect();
        auth()->logout();
    }

    public function test_footer_settings_are_rendered(): void
    {
        app(Settings::class)->set('site.social_links', ['instagram' => 'https://instagram.com/biofy']);

        $this->save('footer', [
            'footer__style' => 'dark', 'footer__logo_height' => 40, 'footer__show_brand_text' => '1',
            'footer__brand_text' => ['tr' => 'Biofy Stüdyo', 'en' => 'Biofy Studio'],
            'site__footer_text' => ['tr' => 'Herkes için bio sayfası.', 'en' => 'Bio pages for everyone.'],
            'footer__product_title' => ['tr' => 'Keşfet', 'en' => 'Explore'], 'footer__company_title' => ['tr' => 'Biz', 'en' => 'Us'],
            'footer__copyright' => ['tr' => '© {year} {site} — bütün hakları saklı', 'en' => ''],
            // footer__show_social unchecked
        ]);

        $html = $this->withSession(['locale' => 'tr'])->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('<footer class="bg-ink text-white">', $html);
        $this->assertStringContainsString('--footer-logo-h:40px', $html);
        foreach (['Biofy Stüdyo', 'Herkes için bio sayfası.', '>Keşfet<', '>Biz<', '© '.date('Y').' Biofy.TR — bütün hakları saklı'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('https://instagram.com/biofy', $html, 'social icons switched off');

        $this->save('footer', ['footer__style' => 'brand', 'footer__logo_height' => 28, 'footer__show_social' => '1', 'footer__copyright' => ['tr' => '', 'en' => '']]);
        $html = $this->withSession(['locale' => 'tr'])->get('/')->getContent();
        $this->assertStringContainsString('<footer class="bg-brand text-ink">', $html);
        $this->assertStringContainsString('https://instagram.com/biofy', $html);
        $this->assertStringNotContainsString('Biofy Stüdyo', $html, 'show_brand_text unchecked');
    }

    public function test_logo_height_is_validated_and_applied(): void
    {
        $admin = $this->makeSuperAdmin();
        $brand = ['site__primary_color' => '#FFCF00', 'site__secondary_color' => '#111111', 'site__accent_color' => '#2563EB', 'profiles__default_theme' => app(Settings::class)->get('profiles.default_theme')];

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', 'brand'), $brand + ['site__logo_height' => 500])->assertSessionHasErrors('site__logo_height');

        $this->save('brand', $brand + ['site__logo_height' => 56]);
        $this->get('/')->assertOk()->assertSee('--logo-h:56px', false)->assertSee('size-(--logo-h)', false);
    }

    public function test_landing_page_sections_and_texts_follow_the_settings(): void
    {
        $html = $this->withSession(['locale' => 'tr'])->get('/')->assertOk()->getContent();
        foreach ([__('site.home.steps_title'), 'Bir bağlantıdan çok daha fazlası', __('site.home.themes_title'), 'Temaları keşfet', 'landing-grid'] as $text) {
            $this->assertStringContainsString(e($text), $html);
        }

        $this->save('home', [
            'home__hero_style' => 'dark', 'home__badge' => ['tr' => 'Yeni sürüm yayında', 'en' => ''],
            'home__hero_title' => ['tr' => 'Merhaba dünya', 'en' => 'Hello'], 'home__hero_subtitle' => ['tr' => 'Alt başlık', 'en' => ''],
            'home__cta_label' => ['tr' => 'Hemen başla', 'en' => ''], 'home__secondary_cta_label' => ['tr' => 'Fiyatlar', 'en' => ''],
            'home__secondary_cta_url' => '/pricing', 'home__features_title' => ['tr' => 'Neler var?', 'en' => ''],
            'home__cta_title' => ['tr' => 'Hadi başlayalım', 'en' => ''], 'home__cta_text' => ['tr' => 'Kapanış metni', 'en' => ''],
            'home__show_features' => '1', // steps, themes and pricing switched off
        ]);

        $html = $this->withSession(['locale' => 'tr'])->get('/')->getContent();
        foreach (['landing-dark', 'Yeni sürüm yayında', 'Merhaba dünya', 'Hemen başla', 'Neler var?', 'Hadi başlayalım', 'Kapanış metni', 'href="'.url('/pricing').'"'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString(e(__('site.home.steps_title')), $html);
        $this->assertStringNotContainsString(e(__('site.home.themes_title')), $html);
        $this->assertStringNotContainsString('>'.e(__('site.pricing.title')).'</h2>', $html, 'pricing section (the menu has the same word)');

        $this->actingAs($this->makeSuperAdmin())->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', 'home'), ['home__secondary_cta_url' => 'javascript:alert(1)'])->assertSessionHasErrors('home__secondary_cta_url');
    }

    public function test_unticked_checkboxes_are_saved(): void
    {
        $settings = app(Settings::class);
        $this->assertTrue($settings->bool('seo.allow_indexing'));

        // An unticked checkbox is not sent at all; it used to be skipped as "keep the current value".
        $this->save('seo', ['seo__default_title' => ['tr' => 'Başlık', 'en' => 'Title'], 'seo__default_description' => ['tr' => '', 'en' => '']]);
        $settings->flush();
        $this->assertFalse($settings->bool('seo.allow_indexing'));
        $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_structured_data_is_valid_json(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, ['slug' => 'jsonld'], true);

        foreach (['/', '/jsonld'] as $path) {
            preg_match('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $this->get($path)->assertOk()->getContent(), $m);
            $data = json_decode($m[1] ?? '', true);
            $this->assertIsArray($data, $path);
            $this->assertSame('https://schema.org', $data['@context'] ?? null, $path);
        }
    }

    public function test_e_mail_icon_is_a_line_icon_on_the_page(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user);
        app(SocialLinkService::class)->create($profile, 'email', 'hello@example.com');

        $html = $this->actingAs($user)->get(route('pages.preview', $profile))->getContent();
        // Stroke-drawn; resources/css/profile.css keeps such icons unfilled (.bp-social svg[stroke]).
        $this->assertMatchesRegularExpression('#class="br-email"[^>]*>\s*<svg[^>]*fill="none" stroke="currentColor"#', $html);
        $this->assertStringContainsString('.bp-social svg[stroke]', (string) file_get_contents(resource_path('css/profile.css')));
    }
}
