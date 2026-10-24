<?php

namespace Tests\Feature;

use App\Services\MediaService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Brand pieces the superadmin controls: bio page badge, admin panel logo, landing hero image. */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function image(string $purpose): \App\Models\MediaFile
    {
        return app(MediaService::class)->store(UploadedFile::fake()->image('logo.png', 400, 120), null, $purpose);
    }

    public function test_bio_page_badge_text_logo_and_link_come_from_the_settings(): void
    {
        Storage::fake('media');
        $this->makeProfile($this->makeUser(), ['slug' => 'rozetli'], true);

        $html = $this->withSession(['locale' => 'tr'])->get('/rozetli')->assertOk()->getContent();
        $this->assertStringContainsString('Biofy.TR ile oluşturuldu', $html, 'default text');
        $this->assertStringContainsString('class="bp-foot-mark"', $html);
        $this->assertStringContainsString('href="'.rtrim(config('app.url'), '/').'/?ref=profile"', $html);

        $this->actingAs($this->makeSuperAdmin())->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', 'branding'), [
                'branding__badge_text' => ['tr' => '{site} ile güçlendirildi', 'en' => 'Powered by {site}'],
                'branding__badge_url' => '/pricing',
                'branding__badge_logo_media_id' => UploadedFile::fake()->image('rozet.png', 200, 60),
            ])->assertSessionHasNoErrors()->assertRedirect();
        auth()->logout();

        $html = $this->withSession(['locale' => 'tr'])->get('/rozetli')->getContent();
        $this->assertStringContainsString('Biofy.TR ile güçlendirildi', $html);
        $this->assertStringContainsString('href="'.rtrim(config('app.url'), '/').'/pricing"', $html);
        $this->assertStringContainsString('class="bp-foot-logo"', $html);
        $this->assertStringNotContainsString('class="bp-foot-mark"', $html);

        $this->actingAs($this->makeSuperAdmin())->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', 'branding'), ['branding__badge_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('branding__badge_url');
    }

    public function test_admin_panel_logo_and_title_are_editable(): void
    {
        Storage::fake('media');
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->withSession(['locale' => 'tr'])->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Biofy Yönetim')->assertSee('rounded-lg bg-brand text-sm font-black text-ink">b</span>', false);

        $siteLogo = $this->image('brand');
        app(Settings::class)->set('site.logo_media_id', $siteLogo->id);
        app(Settings::class)->set('site.admin_title', ['tr' => 'Kontrol Merkezi', 'en' => 'Control Room']);
        $this->app->forgetInstance(\App\View\Composers\LayoutComposer::class);

        $this->actingAs($admin)->withSession(['locale' => 'tr'])->get(route('admin.dashboard'))
            ->assertSee('Kontrol Merkezi')->assertSee('src="'.$siteLogo->url().'"', false);

        $panelLogo = $this->image('brand');
        app(Settings::class)->set('site.admin_logo_media_id', $panelLogo->id);
        $this->app->forgetInstance(\App\View\Composers\LayoutComposer::class);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('src="'.$panelLogo->url().'"', false);
        $this->assertTrue(app(MediaService::class)->isInUse($panelLogo));
    }

    public function test_landing_hero_shows_the_mock_up_or_the_uploaded_image(): void
    {
        Storage::fake('media');

        $this->withSession(['locale' => 'tr'])->get('/')->assertOk()->assertSee(__('site.home.mock_name'))->assertSee('deniz.studio');

        $hero = app(MediaService::class)->store(UploadedFile::fake()->image('hero.png', 2400, 1800), null, 'hero');
        $this->assertSame(1600, $hero->width, 'resized to the hero target');
        app(Settings::class)->set('home.hero_image_media_id', $hero->id);
        $this->app->forgetInstance(\App\View\Composers\LayoutComposer::class);

        $this->get('/')->assertOk()->assertSee('src="'.$hero->url().'"', false)->assertDontSee('deniz.studio');
    }
}
