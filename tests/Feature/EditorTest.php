<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\ProfileBlock;
use App\Services\BlockService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_block_crud_and_persistent_ordering(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user);
        $rev = fn () => $profile->fresh()->draft_revision;

        $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'link', 'revision' => $rev(), 'data' => ['url' => 'example.com/a', 'title' => 'A']])
            ->assertOk()->assertJsonStructure(['message', 'revision', 'html' => ['blocks-list']]);
        $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'heading', 'revision' => $rev(), 'data' => ['text' => 'Title']])->assertOk();
        $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'text', 'revision' => $rev(), 'data' => ['text' => "Line 1\nLine 2"]])->assertOk();

        $link = ProfileBlock::query()->where('type', 'link')->firstOrFail();
        $this->assertSame('https://example.com/a', $link->data['url']);

        $ids = $profile->fresh()->blocks->pluck('id')->reverse()->values()->all();
        $this->actingAs($user)->postJson(route('blocks.reorder', $profile), ['order' => $ids, 'revision' => $rev()])->assertOk();
        $this->assertSame($ids, $profile->fresh()->blocks->pluck('id')->all());

        $this->actingAs($user)->postJson(route('blocks.visibility', $link), ['visible' => 0, 'revision' => $rev()])->assertOk();
        $this->assertFalse($link->fresh()->is_visible);

        $this->actingAs($user)->postJson(route('blocks.duplicate', $link), ['revision' => $rev()])->assertOk();
        $this->assertSame(2, ProfileBlock::query()->where('type', 'link')->count());

        $this->actingAs($user)->deleteJson(route('blocks.destroy', $link), ['revision' => $rev()])->assertOk();
        $this->assertNull($link->fresh());

        $this->actingAs($user)->postJson(route('blocks.reorder', $profile), ['order' => [999999], 'revision' => $rev()])->assertStatus(422);
    }

    public function test_unsafe_urls_are_rejected(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user);

        foreach (['javascript:alert(1)', 'data:text/html,<script>alert(1)</script>', 'vbscript:x', '//evil.com', 'https://user:pw@evil.com'] as $url) {
            $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'link', 'data' => ['url' => $url, 'title' => 'x']])
                ->assertStatus(422)->assertJsonValidationErrors('data.url');
        }

        $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'whatsapp', 'data' => ['phone' => '<script>']])->assertStatus(422);
        $this->assertSame(0, ProfileBlock::query()->whereIn('type', ['link', 'whatsapp'])->count());
    }

    public function test_advanced_blocks_require_the_feature(): void
    {
        $basic = $this->makeUser();
        $profile = $this->makeProfile($basic);

        $this->actingAs($basic)->postJson(route('blocks.store', $profile), ['type' => 'faq', 'data' => ['items' => [['q' => 'Q', 'a' => 'A']]]])->assertStatus(422);
        $this->assertSame(0, ProfileBlock::query()->where('type', 'faq')->count());

        $pro = $this->makeUser([], 'pro');
        $proProfile = $this->makeProfile($pro);
        $this->actingAs($pro)->postJson(route('blocks.store', $proProfile), ['type' => 'faq', 'data' => ['items' => [['q' => 'Q', 'a' => 'A']]]])->assertOk();
    }

    public function test_downgrade_hides_advanced_blocks_on_the_live_page(): void
    {
        $user = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($user, ['slug' => 'downgraded']);
        app(BlockService::class)->create($profile, 'faq', ['data' => ['title' => 'Secret FAQ', 'items' => [['q' => 'Q1', 'a' => 'A1']]]]);
        app(ProfileService::class)->publish($profile->fresh(), $user);
        $this->get('/downgraded')->assertSee('Secret FAQ');

        $this->assignPackage($user, 'basic');
        $this->get('/downgraded')->assertDontSee('Secret FAQ');
    }

    public function test_link_quota_is_enforced(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user);
        $service = app(BlockService::class);

        for ($i = 0; $i < 15; $i++) {
            $service->create($profile->fresh(), 'link', ['data' => ['url' => "https://example.com/{$i}", 'title' => "L{$i}"]]);
        }

        $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'email', 'data' => ['email' => 'a@example.com']])
            ->assertStatus(422)->assertJson(['message' => __('blocks.limit_links')]);
        $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'heading', 'data' => ['text' => 'Still fine']])->assertOk();
    }

    public function test_scheduling_requires_feature(): void
    {
        $basic = $this->makeUser();
        $basicProfile = $this->makeProfile($basic);
        $this->actingAs($basic)->postJson(route('blocks.store', $basicProfile), ['type' => 'link', 'data' => ['url' => 'https://x.com', 'title' => 'x'], 'starts_at' => now()->addDay()->format('Y-m-d H:i')])->assertStatus(422);

        $pro = $this->makeUser(['timezone' => 'UTC'], 'pro');
        $proProfile = $this->makeProfile($pro);
        $this->actingAs($pro)->postJson(route('blocks.store', $proProfile), ['type' => 'link', 'data' => ['url' => 'https://x.com', 'title' => 'x'], 'starts_at' => now()->addDay()->format('Y-m-d H:i')])->assertOk();
    }

    public function test_schedule_window_is_applied_at_render_time_without_cron(): void
    {
        $user = $this->makeUser(['timezone' => 'UTC'], 'pro');
        $profile = $this->makeProfile($user, ['slug' => 'windowed']);
        $service = app(BlockService::class);
        $service->create($profile, 'link', ['data' => ['url' => 'https://now.example.com', 'title' => 'Visible now']]);
        $service->create($profile->fresh(), 'link', ['data' => ['url' => 'https://later.example.com', 'title' => 'Starts tomorrow'], 'starts_at' => now()->addDay()->toDateTimeString()]);
        $service->create($profile->fresh(), 'link', ['data' => ['url' => 'https://soon.example.com', 'title' => 'Ends in an hour'], 'ends_at' => now()->addHour()->toDateTimeString()]);
        app(ProfileService::class)->publish($profile->fresh(), $user);

        $this->get('/windowed')->assertSee('Visible now')->assertDontSee('Starts tomorrow')->assertSee('Ends in an hour');

        $this->travel(2)->hours();
        $this->get('/windowed')->assertDontSee('Ends in an hour');

        $this->travel(1)->days();
        $this->get('/windowed')->assertSee('Starts tomorrow');
    }

    public function test_revision_conflict_is_detected(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user);
        $stale = $profile->draft_revision;

        $this->actingAs($user)->putJson(route('pages.details', $profile), ['display_name' => 'Tab one', 'language' => 'tr', 'revision' => $stale])->assertOk();
        $this->actingAs($user)->putJson(route('pages.details', $profile), ['display_name' => 'Tab two', 'language' => 'tr', 'revision' => $stale])
            ->assertStatus(409)->assertJson(['conflict' => true]);

        $this->assertSame('Tab one', $profile->fresh()->display_name);
    }

    public function test_media_must_belong_to_the_editor(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user);
        $foreign = MediaFile::query()->create(['user_id' => $this->makeUser()->id, 'path' => 'block/x.webp', 'mime' => 'image/webp', 'size' => 10, 'sha256' => str_repeat('0', 64)]);

        $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'image', 'data' => ['media_id' => $foreign->id]])->assertStatus(422)->assertJsonValidationErrors('data.media_id');
        $this->actingAs($user)->putJson(route('pages.details', $profile), ['display_name' => 'x', 'language' => 'tr', 'avatar_media_id' => $foreign->id])->assertStatus(422);
    }

    public function test_social_links_are_validated_per_platform(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user);

        $this->actingAs($user)->postJson(route('social.store', $profile), ['platform' => 'instagram', 'value' => '@mito.tr'])->assertOk();
        $this->assertSame('https://instagram.com/mito.tr', $profile->fresh()->socialLinks->first()->url);

        $this->actingAs($user)->postJson(route('social.store', $profile), ['platform' => 'instagram', 'value' => 'https://evil.com/mito'])->assertStatus(422);
        $this->actingAs($user)->postJson(route('social.store', $profile), ['platform' => 'website', 'value' => 'javascript:alert(1)'])->assertStatus(422);
        $this->actingAs($user)->postJson(route('social.store', $profile), ['platform' => 'unknownplatform', 'value' => 'x'])->assertStatus(422);
    }

    public function test_theme_selection_respects_premium_and_sanitises_settings(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user);

        $this->actingAs($user)->putJson(route('pages.theme', $profile), ['theme_key' => 'neon'])->assertStatus(422);
        $this->actingAs($user)->putJson(route('pages.theme', $profile), ['theme_key' => 'sunny'])->assertOk();
        $this->actingAs($user)->putJson(route('pages.theme', $profile), ['theme_key' => 'sunny', 'settings' => ['bg_color' => '#123456', 'font' => 'fraunces', 'text_color' => 'red;}</style>']])->assertOk();

        $settings = $profile->fresh()->theme_settings;
        $this->assertSame('sunny', $profile->fresh()->theme_key);
        $this->assertSame('#123456', $settings['bg_color']);
        $this->assertArrayNotHasKey('font', $settings, 'advanced customisation is not in Basic');
        $this->assertArrayNotHasKey('text_color', $settings);

        $pro = $this->makeUser([], 'pro');
        $proProfile = $this->makeProfile($pro);
        $this->actingAs($pro)->putJson(route('pages.theme', $proProfile), ['theme_key' => 'neon'])->assertOk();
        $this->actingAs($pro)->putJson(route('pages.theme', $proProfile), ['theme_key' => 'neon', 'settings' => ['font' => 'fraunces']])->assertOk();
        $this->assertSame('fraunces', $proProfile->fresh()->theme_settings['font']);
    }
}
