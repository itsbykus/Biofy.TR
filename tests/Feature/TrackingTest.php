<?php

namespace Tests\Feature;

use App\Services\Entitlements;
use App\Services\ProfileService;
use App\Services\Settings;
use App\Support\FeatureCatalogUpgrades;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Google / Meta / TikTok tags: validated IDs, consent bar, CSP only where used. */
class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function csp($response): string
    {
        return (string) $response->headers->get('Content-Security-Policy');
    }

    public function test_site_tags_switch_the_cookie_notice_to_a_consent_bar(): void
    {
        $plain = $this->get('/')->assertOk();
        $this->assertStringNotContainsString('googletagmanager', $this->csp($plain));
        $plain->assertDontSee('id="biofy-tracking"', false)->assertSee('id="cookie-notice"', false);

        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', 'tracking'), ['tracking__ga4' => 'G-<script>', 'tracking__meta' => 'abc'])
            ->assertSessionHasErrors(['tracking__ga4', 'tracking__meta']);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', 'tracking'), ['tracking__ga4' => 'g-abc123xyz', 'tracking__meta' => '123456789012345', 'tracking__tiktok' => ''])
            ->assertSessionHasNoErrors();
        auth()->logout();
        $this->app->forgetInstance(\App\View\Composers\LayoutComposer::class);

        $page = $this->withSession(['locale' => 'tr'])->get('/pricing')->assertOk();
        $page->assertSee('<script type="application/json" id="biofy-tracking">{"scope":"site","ids":{"ga4":"G-ABC123XYZ","meta":"123456789012345"},"clicks":false}</script>', false)
            ->assertSee('id="biofy-consent"', false)->assertSee('data-consent="denied"', false)
            ->assertSee('data-consent-open', false)->assertDontSee('id="cookie-notice"', false);

        $csp = $this->csp($page);
        $this->assertStringContainsString('script-src \'self\' \'nonce-', $csp);
        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
        $this->assertStringContainsString('https://connect.facebook.net', $csp);
        $this->assertStringNotContainsString('analytics.tiktok.com', $csp, 'only providers in use');
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);

        // Panels never load marketing tags.
        $dashboard = $this->actingAs($this->makeUser())->get(route('dashboard'))->assertOk();
        $this->assertStringNotContainsString('googletagmanager', $this->csp($dashboard));
        $dashboard->assertDontSee('id="biofy-tracking"', false);
    }

    public function test_bio_page_pixels_need_the_plan_and_load_only_on_the_public_page(): void
    {
        $basic = $this->makeUser();
        $basicProfile = $this->makeProfile($basic);
        $this->actingAs($basic)->put(route('pages.tracking', $basicProfile), ['tracking' => ['meta' => '123456789012345']])->assertForbidden();

        $pro = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($pro, ['slug' => 'pikselli'], true);

        $this->actingAs($pro)->putJson(route('pages.tracking', $profile), ['tracking' => ['ga4' => 'nope']])->assertStatus(422)->assertJsonValidationErrors('tracking.ga4');
        $this->actingAs($pro)->putJson(route('pages.tracking', $profile), ['tracking' => ['meta' => ' 123456789012345 ', 'tiktok' => 'cabcdefghij1234567', 'gtm' => '']])->assertOk();
        $this->assertSame(['meta' => '123456789012345', 'tiktok' => 'CABCDEFGHIJ1234567'], $profile->fresh()->tracking);

        $this->actingAs($pro)->get(route('pages.edit', $profile).'?tab=seo')->assertOk()->assertSee('123456789012345');
        auth()->logout();

        $page = $this->get('/pikselli')->assertOk();
        $page->assertSee('"scope":"p'.$profile->id.'"', false)->assertSee('class="bp-consent"', false)->assertSee('data-consent-open', false);
        $csp = $this->csp($page);
        $this->assertStringContainsString('https://connect.facebook.net', $csp);
        $this->assertStringContainsString('https://analytics.tiktok.com', $csp);
        $this->assertStringNotContainsString('googletagmanager', $csp);

        // The editor preview never loads them.
        $this->actingAs($pro)->get(route('pages.preview', $profile))->assertOk()->assertDontSee('id="biofy-tracking"', false);
        auth()->logout();

        // Plan ends: the pixels stop with it.
        DB::table('user_package_assignments')->where('user_id', $pro->id)->delete();
        app(Entitlements::class)->flushUser($pro->fresh());
        $this->get('/pikselli')->assertOk()->assertDontSee('id="biofy-tracking"', false);
    }

    public function test_feature_upgrade_is_idempotent_and_survives_old_backups(): void
    {
        $this->assertFalse(FeatureCatalogUpgrades::apply(), 'already present after migrations + seeders');

        DB::table('package_features')->where('feature_key', 'shortlinks.max')->delete();
        DB::table('feature_definitions')->where('key', 'shortlinks.max')->delete();
        $this->assertTrue(FeatureCatalogUpgrades::apply());
        app(Entitlements::class)->flushAll();

        $this->assertSame(10, app(Entitlements::class)->limit($this->makeUser(), Entitlements::MAX_SHORT_LINKS));
        $this->assertSame(-1, app(Entitlements::class)->limit($this->makeUser([], 'premium'), Entitlements::MAX_SHORT_LINKS));
        $this->assertTrue(app(Entitlements::class)->allows($this->makeUser([], 'pro'), Entitlements::TRACKING_PIXELS));
        $this->assertNotNull(app(Settings::class)->get('system.features_version'));
    }

    public function test_profile_service_publish_keeps_tracking_out_of_the_snapshot(): void
    {
        $pro = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($pro);
        $profile->forceFill(['tracking' => ['meta' => '123456789012345']])->save();

        $version = app(ProfileService::class)->publish($profile->fresh(), $pro);
        $this->assertStringNotContainsString('123456789012345', json_encode($version->snapshot));
    }
}
