<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Services\MediaService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Link buttons: automatic brand icons, chosen icons and background images. */
class LinkButtonTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_icons_and_background_images_on_published_buttons(): void
    {
        Storage::fake('media');
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, ['slug' => 'butonlar']);
        $rev = fn () => $profile->fresh()->draft_revision;
        $bg = app(MediaService::class)->store(UploadedFile::fake()->image('bg.jpg', 1200, 400), $user, 'block');

        $add = fn (array $data) => $this->actingAs($user)->postJson(route('blocks.store', $profile), ['type' => 'link', 'revision' => $rev(), 'data' => $data]);

        $add(['url' => 'https://instagram.com/biofy', 'title' => 'Instagram hesabım'])->assertOk();
        $add(['url' => 'https://example.com/shop', 'title' => 'Mağaza', 'icon' => 'icon:shopping-bag'])->assertOk();
        $add(['url' => 'https://youtube.com/@biofy', 'title' => 'Simgesiz', 'icon' => 'none'])->assertOk();
        $add(['url' => 'https://example.com/event', 'title' => 'Etkinlik', 'bg_media_id' => $bg->id])->assertOk();

        // Unknown icons and someone else's images are refused.
        $add(['url' => 'https://example.com', 'title' => 'x', 'icon' => 'brand:nope'])->assertStatus(422)->assertJsonValidationErrors('data.icon');
        $other = app(MediaService::class)->store(UploadedFile::fake()->image('o.jpg', 300, 300), $this->makeUser(), 'block');
        $add(['url' => 'https://example.com', 'title' => 'x', 'bg_media_id' => $other->id])->assertStatus(422)->assertJsonValidationErrors('data.bg_media_id');

        app(ProfileService::class)->publish($profile->fresh(), $user);
        $html = $this->get('/butonlar')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<svg[^>]*class="[^"]*bp-btn-icon"[^>]*aria-hidden="true"[^>]*>.*?</svg>\s*<span class="bp-btn-text">\s*<span class="bp-btn-title">Instagram hesabım#s', $html, 'brand icon recognised from the URL');
        $this->assertMatchesRegularExpression('#class="[^"]*bp-btn-icon"[^>]*>.{0,600}?</svg>\s*<span class="bp-btn-text">\s*<span class="bp-btn-title">Mağaza#s', $html);
        $this->assertMatchesRegularExpression('#rel="noopener nofollow ugc">\s*<span class="bp-btn-text">\s*<span class="bp-btn-title">Simgesiz#s', $html, 'no icon when switched off');
        $this->assertStringContainsString('bp-btn--image', $html);
        $this->assertStringContainsString('<img class="bp-btn-bg" src="'.$bg->url().'"', $html);

        // The background picture counts as used (not offered for deletion).
        $this->assertTrue(app(MediaService::class)->isInUse(MediaFile::query()->findOrFail($bg->id)));
    }
}
