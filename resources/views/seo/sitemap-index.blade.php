{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap><loc>{{ route('sitemap.pages') }}</loc></sitemap>
@for ($i = 1; $i <= $chunks; $i++)
    <sitemap><loc>{{ route('sitemap.profiles', $i) }}</loc></sitemap>
@endfor
</sitemapindex>
