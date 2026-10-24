<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\MediaFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAndBlogSeoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_uploads_are_stored_as_webp_and_raw_svg_is_refused(): void
    {
        Storage::fake('media');
        $user = $this->makeUser();

        foreach (['photo.jpg', 'logo.png', 'anim.gif'] as $name) {
            $this->actingAs($user)->postJson(route('media.store'), ['file' => UploadedFile::fake()->image($name, 900, 600), 'purpose' => 'block'])->assertCreated();
        }

        foreach (MediaFile::query()->where('user_id', $user->id)->get() as $media) {
            $this->assertSame('image/webp', $media->mime);
            $this->assertStringEndsWith('.webp', $media->path);
            $this->assertSame('RIFF', substr(Storage::disk('media')->get($media->path), 0, 4));
        }

        // SVG is converted in the browser; a raw SVG reaching the server is refused with a clear message.
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>');
        $this->actingAs($user)->postJson(route('media.store'), ['file' => $svg, 'purpose' => 'block'])
            ->assertStatus(422)->assertJson(['message' => __('media.svg_needs_browser')]);
    }

    public function test_blog_post_seo_settings(): void
    {
        Storage::fake('media');
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->post(route('admin.blog.store'), [
            'title' => ['tr' => 'Uzun ve açıklayıcı bir yazı başlığı'],
            'excerpt' => ['tr' => 'Özet metni.'],
            'body' => ['tr' => 'İçerik'],
            'seo_title' => ['tr' => 'Kısa SEO başlığı'],
            'meta_description' => ['tr' => 'Arama motoru açıklaması.'],
            'seo_image' => UploadedFile::fake()->image('share.png', 1600, 1000),
            'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $post = BlogPost::query()->firstOrFail();
        $this->assertSame([1200, 630], [$post->seoImage->width, $post->seoImage->height]);
        auth()->logout();

        $html = $this->withSession(['locale' => 'tr'])->get($post->url())->assertOk()->getContent();
        $this->assertStringContainsString('<title>Kısa SEO başlığı · Biofy.TR</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Arama motoru açıklaması.">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.url($post->seoImage->url()).'">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.$post->url().'">', $html);
        $this->assertStringNotContainsString('noindex', $html);
        $this->assertStringContainsString('<h1 class="mt-6', $html);
        $this->assertStringContainsString('Uzun ve açıklayıcı bir yazı başlığı</h1>', $html, 'the page heading keeps the real title');
        $this->get(route('sitemap.pages'))->assertSee($post->url(), false);

        $this->actingAs($admin)->put(route('admin.blog.update', $post), [
            'title' => ['tr' => 'Uzun ve açıklayıcı bir yazı başlığı'], 'slug' => $post->slug, 'is_published' => '1',
            'seo_noindex' => '1', 'canonical_url' => 'https://medium.com/@biofy/ilk-yazi',
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.blog.update', $post), [
            'title' => ['tr' => 'x'], 'slug' => $post->slug, 'canonical_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('canonical_url');
        auth()->logout();

        $html = $this->get($post->url())->assertOk()->getContent();
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://medium.com/@biofy/ilk-yazi">', $html);
        $this->get(route('sitemap.pages'))->assertDontSee($post->url(), false);
    }
}
