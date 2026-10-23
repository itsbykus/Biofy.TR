<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Services\BlockService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Analytics days are Istanbul days while now() is UTC: at midday both are the same date,
        // so the suite does not depend on the hour it runs (it failed between 21:00 and 24:00 UTC).
        $this->travelTo(now('Europe/Istanbul')->setTime(12, 0));
    }

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private function publishedProfileWithLink(string $slug = 'stats', string $package = 'pro'): array
    {
        $user = $this->makeUser([], $package);
        $profile = $this->makeProfile($user, ['slug' => $slug]);
        $block = app(BlockService::class)->create($profile, 'link', ['data' => ['url' => 'https://target.example.com/page', 'title' => 'Target']]);
        app(ProfileService::class)->publish($profile->fresh(), $user);

        return [$user, $profile->fresh(), $block];
    }

    public function test_views_are_recorded_deduplicated_and_bots_ignored(): void
    {
        [, $profile] = $this->publishedProfileWithLink();

        $this->flushHeaders()->withHeaders(['User-Agent' => self::BROWSER, 'Referer' => 'https://l.instagram.com/x'])->get('/stats')->assertOk();
        $this->flushHeaders()->withHeaders(['User-Agent' => self::BROWSER])->get('/stats')->assertOk(); // refresh within 30 min
        $this->flushHeaders()->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->get('/stats')->assertOk();
        $this->flushHeaders()->withHeaders(['User-Agent' => self::BROWSER, 'Sec-Purpose' => 'prefetch'])->get('/stats')->assertOk();
        $this->flushHeaders()->withHeaders(['User-Agent' => ''])->get('/stats')->assertOk();

        $this->assertSame(1, AnalyticsEvent::query()->where('type', 'view')->count());
        $event = AnalyticsEvent::query()->first();
        $this->assertSame('instagram.com', $event->referrer_host);
        $this->assertSame('mobile', $event->device);
        $this->assertTrue($event->is_unique);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $event->visitor_hash);

        // No raw IP address is stored anywhere in analytics.
        $this->assertStringNotContainsString('127.0.0.1', json_encode(DB::table('analytics_events')->get()));

        $this->travel(31)->minutes();
        $this->flushHeaders()->withHeaders(['User-Agent' => self::BROWSER])->get('/stats');
        $this->assertSame(2, AnalyticsEvent::query()->where('type', 'view')->count());
        $this->assertSame(1, AnalyticsEvent::query()->where('is_unique', true)->count(), 'same visitor, same day = one unique');

        $summary = DB::table('analytics_daily_summaries')->where('profile_id', $profile->id)->where('block_id', 0)->first();
        $this->assertSame(2, (int) $summary->views);
        $this->assertSame(1, (int) $summary->unique_visitors);
    }

    public function test_clicks_redirect_to_the_stored_destination_and_are_counted(): void
    {
        [, $profile, $block] = $this->publishedProfileWithLink();

        $this->flushHeaders()->withHeaders(['User-Agent' => self::BROWSER])->get("/go/{$profile->id}/{$block->id}")->assertRedirect('https://target.example.com/page');
        $this->assertSame(1, AnalyticsEvent::query()->where('type', 'click')->where('block_id', $block->id)->count());

        // Unknown or other-profile block ids never redirect anywhere external (no open redirect).
        $this->get("/go/{$profile->id}/999999")->assertRedirect('/'.$profile->slug);
        $this->get("/go/{$profile->id}/{$block->id}?url=https://evil.com")->assertRedirect('https://target.example.com/page');

        $other = $this->publishedProfileWithLink('other');
        $this->get("/go/{$profile->id}/{$other[2]->id}")->assertRedirect('/'.$profile->slug);
    }

    public function test_dashboard_shows_only_own_data_and_date_filters_work(): void
    {
        [$owner, $profile] = $this->publishedProfileWithLink();
        $this->flushHeaders()->withHeaders(['User-Agent' => self::BROWSER])->get('/stats');

        $this->actingAs($owner)->get(route('analytics.index', ['from' => now()->toDateString(), 'to' => now()->toDateString()]))->assertOk()->assertViewHas('overview', fn ($o) => $o['totals']['views'] === 1);
        $this->actingAs($owner)->get(route('analytics.index', ['from' => now()->subDays(10)->toDateString(), 'to' => now()->subDays(5)->toDateString()]))->assertViewHas('overview', fn ($o) => $o['totals']['views'] === 0);

        $stranger = $this->makeUser([], 'pro');
        $this->actingAs($stranger)->get(route('analytics.index', ['profile' => $profile->id]))->assertNotFound();
        $this->actingAs($stranger)->get(route('analytics.export', ['profile' => $profile->id]))->assertNotFound();
    }

    public function test_detailed_breakdowns_and_export_require_features(): void
    {
        [$basicUser] = $this->publishedProfileWithLink('basicstats', 'basic');
        $this->actingAs($basicUser)->get(route('analytics.index'))->assertOk()->assertViewHas('breakdowns', []);
        $this->actingAs($basicUser)->get(route('analytics.export'))->assertRedirect(route('plan.show'));

        [$pro] = $this->publishedProfileWithLink('prostats');
        $this->flushHeaders()->withHeaders(['User-Agent' => self::BROWSER])->get('/prostats');
        $this->actingAs($pro)->get(route('analytics.index'))->assertViewHas('breakdowns', fn ($b) => isset($b['device']));
        $csv = $this->actingAs($pro)->get(route('analytics.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString(now()->toDateString(), $csv);
    }

    public function test_csv_neutralises_formula_injection(): void
    {
        $user = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($user, ['slug' => 'csvtest']);
        $block = app(BlockService::class)->create($profile, 'link', ['data' => ['url' => 'https://x.example.com', 'title' => '=HYPERLINK("http://evil")']]);
        app(ProfileService::class)->publish($profile->fresh(), $user);
        $this->flushHeaders()->withHeaders(['User-Agent' => self::BROWSER])->get("/go/{$profile->id}/{$block->id}");

        $csv = $this->actingAs($user)->get(route('analytics.export'))->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_preview_is_never_counted(): void
    {
        [$owner, $profile] = $this->publishedProfileWithLink();
        $this->actingAs($owner)->withHeaders(['User-Agent' => self::BROWSER])->get(route('pages.preview', $profile))->assertOk();
        $this->assertSame(0, AnalyticsEvent::query()->count());
    }
}
