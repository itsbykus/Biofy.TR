<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\MediaFile;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_staff_publish_a_post_that_visitors_can_read(): void
    {
        Storage::fake('media');
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->post(route('admin.blog.store'), [
            'title' => ['tr' => 'Merhaba Dünya', 'en' => 'Hello World'],
            'excerpt' => ['tr' => 'İlk yazımız.', 'en' => ''],
            'body' => ['tr' => "## Başlık\n\nBir paragraf.\n\n<script>alert(1)</script>", 'en' => ''],
            'is_published' => '1',
            'cover' => UploadedFile::fake()->image('kapak.jpg', 1920, 1080),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $post = BlogPost::query()->firstOrFail();
        $this->assertSame('merhaba-dunya', $post->slug, 'slug generated from the Turkish title');
        $this->assertTrue($post->isVisible());
        $this->assertSame($admin->id, $post->author_id);
        $this->assertNotNull($post->cover);
        $this->assertSame([1600, 900], [$post->cover->width, $post->cover->height]);
        $this->assertTrue(app(MediaService::class)->isInUse($post->cover), 'cover protected from the media cleanup');
        auth()->logout();

        $this->withSession(['locale' => 'tr'])->get(route('blog.index'))->assertOk()->assertSee('Merhaba Dünya')->assertSee('İlk yazımız.');

        $html = $this->withSession(['locale' => 'tr'])->get('/blog/merhaba-dunya')->assertOk()->getContent();
        $this->assertStringContainsString('<h2>Başlık</h2>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html, 'raw HTML is escaped');
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.url($post->cover->url()).'">', $html);
        $this->assertMatchesRegularExpression('#<script type="application/ld\+json"[^>]*>(.+?)</script>#s', $html);
        preg_match('#<script type="application/ld\+json"[^>]*>(.+?)</script>#s', $html, $ld);
        $this->assertSame('BlogPosting', json_decode($ld[1], true)['@type'] ?? null);

        $this->withSession(['locale' => 'en'])->get('/blog/merhaba-dunya')->assertOk()->assertSee('Hello World');
        $this->get(route('sitemap.pages'))->assertOk()->assertSee(url('/blog/merhaba-dunya'), false)->assertSee(url('/blog'), false);
    }

    public function test_drafts_and_scheduled_posts_stay_hidden(): void
    {
        BlogPost::query()->create(['slug' => 'taslak', 'title' => ['tr' => 'Taslak yazı'], 'is_published' => false]);
        BlogPost::query()->create(['slug' => 'yarin', 'title' => ['tr' => 'Yarınki yazı'], 'is_published' => true, 'published_at' => now()->addDay()]);
        BlogPost::query()->create(['slug' => 'bugun', 'title' => ['tr' => 'Bugünkü yazı'], 'is_published' => true, 'published_at' => now()->subMinute()]);

        $this->get(route('blog.index'))->assertOk()->assertSee('Bugünkü yazı')->assertDontSee('Taslak yazı')->assertDontSee('Yarınki yazı');
        $this->get('/blog/taslak')->assertNotFound();
        $this->get('/blog/yarin')->assertNotFound();
        $this->get(route('sitemap.pages'))->assertDontSee('/blog/yarin', false);

        $this->actingAs($this->makeSuperAdmin())->get(route('admin.blog.index'))->assertOk()
            ->assertSee('Taslak yazı')->assertSee('Yarınki yazı')->assertSee(__('admin.blog.scheduled'));
    }

    public function test_a_future_date_typed_in_the_site_time_zone_schedules_the_post(): void
    {
        $this->actingAs($this->makeSuperAdmin())->post(route('admin.blog.store'), [
            'title' => ['tr' => 'Planlı'], 'slug' => 'planli', 'is_published' => '1',
            'published_at' => now('Europe/Istanbul')->addHours(2)->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();

        $post = BlogPost::query()->where('slug', 'planli')->firstOrFail();
        $this->assertFalse($post->isVisible());
        $this->assertEqualsWithDelta(now()->addHours(2)->getTimestamp(), $post->published_at->getTimestamp(), 60);
    }

    public function test_only_content_staff_manage_posts_and_deleting_removes_the_cover(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('admin.blog.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.blog.store'), ['title' => ['tr' => 'x']])->assertForbidden();

        Storage::fake('media');
        $cover = app(MediaService::class)->store(UploadedFile::fake()->image('c.png', 800, 450), null, 'blog');
        $post = BlogPost::query()->create(['slug' => 'silinecek', 'title' => ['tr' => 'Silinecek'], 'cover_media_id' => $cover->id, 'is_published' => true, 'published_at' => now()]);

        $this->actingAs($this->makeSuperAdmin())->delete(route('admin.blog.destroy', $post))->assertRedirect(route('admin.blog.index'));
        $this->assertNull($post->fresh());
        $this->assertNull(MediaFile::query()->find($cover->id));
        Storage::disk('media')->assertMissing($cover->path);
    }
}
