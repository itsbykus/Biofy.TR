<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Search engine essentials on the public site. */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** @return list<array> decoded JSON-LD blocks of a page (each must be valid JSON) */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.+?)</script>#s', $html, $matches);

        return array_map(fn ($json) => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $matches[1]);
    }

    public function test_home_page_meta_and_structured_data(): void
    {
        app(Settings::class)->set('site.social_links', ['instagram' => 'https://instagram.com/biofy', 'bad' => 'javascript:alert(1)']);

        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', 'seo'), ['seo__default_title' => ['tr' => 'Biofy', 'en' => ''], 'seo__default_description' => ['tr' => 'Açıklama', 'en' => ''], 'seo__allow_indexing' => '1', 'seo__google_verification' => '"><script>'])
            ->assertSessionHasErrors('seo__google_verification');
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.settings.update', 'seo'), ['seo__default_title' => ['tr' => 'Biofy', 'en' => ''], 'seo__default_description' => ['tr' => 'Açıklama', 'en' => ''], 'seo__allow_indexing' => '1', 'seo__google_verification' => 'AbC123_def-456GhI', 'seo__bing_verification' => '1234567890ABCDEF'])
            ->assertSessionHasNoErrors();
        auth()->logout();
        $this->app->forgetInstance(\App\View\Composers\LayoutComposer::class);

        $html = $this->withSession(['locale' => 'tr'])->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('<meta name="google-site-verification" content="AbC123_def-456GhI">', $html);
        $this->assertStringContainsString('<meta name="msvalidate.01" content="1234567890ABCDEF">', $html);
        $this->assertStringNotContainsString('yandex-verification', $html);
        $this->assertStringContainsString('<meta name="twitter:title" content="Biofy">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'">', $html);

        $graph = $this->jsonLd($html)[0]['@graph'];
        $this->assertSame(['WebSite', 'Organization'], array_column($graph, '@type'));
        $this->assertSame(['https://instagram.com/biofy'], $graph[1]['sameAs'], 'only https profiles');
    }

    public function test_blog_list_pages_are_their_own_canonical_and_posts_have_breadcrumbs(): void
    {
        foreach (range(1, 11) as $i) {
            BlogPost::query()->create(['slug' => 'yazi-'.$i, 'title' => ['tr' => 'Yazı '.$i], 'is_published' => true, 'published_at' => now()->subDays($i)]);
        }

        $first = $this->get('/blog')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.route('blog.index').'">', $first);
        $this->assertStringContainsString('<link rel="next" href="'.route('blog.index').'?page=2">', $first);
        $this->assertSame('Blog', $this->jsonLd($first)[0]['@type']);
        $this->assertCount(9, $this->jsonLd($first)[0]['blogPost']);

        $second = $this->get('/blog?page=2')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.route('blog.index').'?page=2">', $second);
        $this->assertStringContainsString('<link rel="prev" href="'.route('blog.index').'?page=1">', $second);

        $post = $this->get('/blog/yazi-1')->assertOk()->getContent();
        $types = array_column($this->jsonLd($post), '@type');
        $this->assertSame(['BlogPosting', 'BreadcrumbList'], $types);
        $this->assertStringContainsString('<meta property="article:published_time"', $post);

        $sitemap = $this->get(route('sitemap.pages'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<loc>'.preg_quote(url('/blog'), '#').'</loc>\s*<lastmod>#', $sitemap);
    }
}
