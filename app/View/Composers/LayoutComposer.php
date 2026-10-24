<?php

namespace App\View\Composers;

use App\Models\MediaFile;
use App\Services\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

/** Brand, menus and announcement shared by every page layout. */
class LayoutComposer
{
    private const MEDIA_KEYS = ['site.logo_media_id', 'site.favicon_media_id', 'seo.og_image_media_id', 'footer.logo_media_id',
        'site.admin_logo_media_id', 'home.hero_image_media_id', 'branding.badge_logo_media_id'];

    private ?array $cache = null;

    public function __construct(private readonly Settings $settings)
    {
    }

    public function compose(View $view): void
    {
        $this->cache ??= $this->build();
        $view->with('site', $this->cache);
    }

    private function build(): array
    {
        $media = [];

        try {
            $ids = array_filter(array_map(fn ($key) => $this->settings->get($key), self::MEDIA_KEYS));
            $media = $ids ? Cache::remember('biofy.brand_media.'.implode('-', $ids), 3600, fn () => MediaFile::query()->whereIn('id', $ids)->get()->mapWithKeys(fn ($m) => [$m->id => $m->url()])->all()) : [];
        } catch (Throwable) {
            // Before installation / DB unavailable: fall back to the text logo.
        }

        $menu = fn (string $key) => collect($this->settings->get($key) ?: [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['url']))
            ->map(fn ($item) => [
                'label' => $item['label'][app()->getLocale()] ?? '' ?: ($item['label']['tr'] ?? $item['label']['en'] ?? ''),
                'url' => safe_internal_url($item['url']),
                'external' => str_starts_with((string) $item['url'], 'https://'),
            ])
            ->values()->all();

        $name = (string) $this->settings->get('site.name');
        $logo = $media[$this->settings->get('site.logo_media_id')] ?? null;
        // Pixel heights end up in a CSS variable: clamped integers only.
        $height = fn (string $key, int $default) => max(16, min(96, (int) ($this->settings->get($key) ?: $default)));

        return [
            'name' => $name,
            'tagline' => $this->settings->text('site.tagline'),
            'description' => $this->settings->text('site.description'),
            'logo' => $logo,
            'logo_height' => $height('site.logo_height', 32),
            'footer' => [
                'style' => in_array($this->settings->get('footer.style'), ['light', 'dark', 'brand'], true) ? $this->settings->get('footer.style') : 'light',
                'logo' => $media[$this->settings->get('footer.logo_media_id')] ?? $logo,
                'logo_height' => $height('footer.logo_height', 28),
                'brand_text' => $this->settings->text('footer.brand_text') ?: $name,
                'show_brand_text' => $this->settings->bool('footer.show_brand_text'),
                'show_social' => $this->settings->bool('footer.show_social'),
                'product_title' => $this->settings->text('footer.product_title') ?: __('site.footer.product'),
                'company_title' => $this->settings->text('footer.company_title') ?: __('site.footer.company'),
                'copyright' => strtr($this->settings->text('footer.copyright'), ['{year}' => date('Y'), '{site}' => $name]),
            ],
            'admin' => [
                'logo' => $media[$this->settings->get('site.admin_logo_media_id')] ?? $logo,
                'title' => $this->settings->text('site.admin_title') ?: __('admin.nav.title'),
            ],
            'hero_image' => $media[$this->settings->get('home.hero_image_media_id')] ?? null,
            'badge' => [
                'text' => strtr($this->settings->text('branding.badge_text') ?: __('profile.made_with', ['site' => '{site}']), ['{site}' => $name]),
                'logo' => $media[$this->settings->get('branding.badge_logo_media_id')] ?? null,
                // Absolute on the main site: bio pages may be served on a custom domain.
                'url' => $this->badgeUrl((string) $this->settings->get('branding.badge_url')),
            ],
            'favicon' => $media[$this->settings->get('site.favicon_media_id')] ?? null,
            'og_image' => $media[$this->settings->get('seo.og_image_media_id')] ?? null,
            'primary' => (string) $this->settings->get('site.primary_color'),
            'header_menu' => $menu('site.header_menu'),
            'footer_menu' => $menu('site.footer_menu'),
            'footer_text' => $this->settings->text('site.footer_text'),
            'social' => (array) $this->settings->get('site.social_links'),
            'support_email' => (string) $this->settings->get('site.support_email'),
            'cookie_notice' => $this->settings->bool('privacy.cookie_notice'),
            'announcement' => $this->settings->bool('announcement.enabled') && $this->settings->text('announcement.text') !== '' ? [
                'text' => $this->settings->text('announcement.text'),
                'url' => $this->settings->get('announcement.url') ? safe_internal_url($this->settings->get('announcement.url')) : null,
                'style' => $this->settings->get('announcement.style'),
            ] : null,
            'registration' => $this->settings->bool('registration.enabled'),
        ];
    }

    private function badgeUrl(string $url): string
    {
        $base = rtrim((string) config('app.url'), '/');

        if ($url === '') {
            return $base.'/?ref=profile';
        }

        return preg_match('#^/(?!/)#', $url) ? $base.$url : (\App\Support\SafeUrl::normalize($url) ?? $base.'/');
    }

    /** Settings changed during this request (admin save) — rebuild on next render. */
    public function forget(): void
    {
        $this->cache = null;
    }
}
