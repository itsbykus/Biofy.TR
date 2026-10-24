<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Profile;
use App\Services\Settings;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    private const PER_SITEMAP = 5000;

    public function __construct(private readonly Settings $settings)
    {
    }

    public function robots(): Response
    {
        $base = rtrim((string) config('app.url'), '/');

        $lines = ['User-agent: *'];

        if ($this->settings->bool('seo.allow_indexing')) {
            foreach (['/admin', '/dashboard', '/login', '/register', '/forgot-password', '/reset-password', '/go/', '/s/', '/api/', '/cron/', '/email/', '/two-factor', '/confirm-password'] as $path) {
                $lines[] = 'Disallow: '.$path;
            }
            $lines[] = 'Allow: /';
            $lines[] = '';
            $lines[] = 'Sitemap: '.$base.'/sitemap.xml';
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /** Sitemap index -> static pages + chunks of published, indexable profiles. */
    public function index(): Response
    {
        $count = $this->indexableProfiles()->count();
        $chunks = max(1, (int) ceil($count / self::PER_SITEMAP));

        return response()->view('seo.sitemap-index', ['chunks' => $chunks], 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function pages(): Response
    {
        $latestPost = BlogPost::query()->visible()->max('updated_at');
        $urls = collect(['/', '/features', '/themes', '/pricing', '/contact', '/blog'])->map(fn ($p) => ['loc' => url($p), 'lastmod' => $p === '/blog' ? $latestPost : null]);

        Page::query()->where('is_published', true)->get(['slug', 'updated_at'])->each(function (Page $page) use ($urls) {
            $path = in_array($page->slug, ['about', 'help', 'privacy', 'terms', 'cookies', 'kvkk'], true) ? '/'.$page->slug : '/page/'.$page->slug;
            $urls->push(['loc' => url($path), 'lastmod' => $page->updated_at]);
        });

        BlogPost::query()->visible()->where('seo_noindex', false)->whereNull('canonical_url')->get(['slug', 'updated_at'])->each(function (BlogPost $post) use ($urls) {
            $urls->push(['loc' => $post->url(), 'lastmod' => $post->updated_at]);
        });

        return response()->view('seo.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function profiles(int $chunk): Response
    {
        abort_if($chunk < 1, 404);

        $urls = $this->indexableProfiles()->with('customDomains')->orderBy('id')
            ->skip(($chunk - 1) * self::PER_SITEMAP)->take(self::PER_SITEMAP)
            ->get(['id', 'slug', 'published_at', 'user_id'])
            // A profile served on its own domain is listed once, under its canonical URL.
            ->map(fn (Profile $p) => ['loc' => $p->canonicalUrl(), 'lastmod' => $p->published_at]);

        return response()->view('seo.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    private function indexableProfiles()
    {
        return Profile::query()->publiclyVisible()->where('seo_noindex', false);
    }
}
