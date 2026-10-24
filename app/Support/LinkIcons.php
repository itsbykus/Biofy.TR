<?php

namespace App\Support;

/**
 * Icons for link buttons: recognised from the destination URL, or chosen by the user.
 * Stored choice (block data "icon"): '' = automatic, 'none', 'brand:<key>' or 'icon:<name>'.
 */
final class LinkIcons
{
    /** UI icons a user may pick for a button (all exist in resources/icons/ui.json). */
    public const UI = [
        'link', 'globe', 'mail', 'phone', 'message-circle', 'send', 'calendar', 'map-pin', 'house', 'user', 'users',
        'briefcase', 'shopping-bag', 'shopping-cart', 'tag', 'gift', 'ticket', 'heart', 'star', 'sparkles', 'zap',
        'rocket', 'music', 'mic', 'podcast', 'play', 'video', 'camera', 'image', 'book-open', 'newspaper',
        'file-text', 'download', 'graduation-cap', 'gamepad-2', 'coffee', 'utensils', 'palette', 'code',
    ];

    /**
     * Host rules, most specific first. Value: brand key (resources/icons/brands.json) or 'icon:<ui name>'
     * for services whose logos are not in the bundled icon set.
     */
    private const HOSTS = [
        'music.youtube.com' => 'youtube_music',
        'music.apple.com' => 'apple_music',
        'apps.apple.com' => 'app_store',
        'play.google.com' => 'google_play',
        'maps.google.com' => 'google_maps',
        'maps.app.goo.gl' => 'google_maps',
        'drive.google.com' => 'google_drive',
        'docs.google.com' => 'google_drive',
        'meet.google.com' => 'google_meet',
        'calendar.google.com' => 'google_calendar',
        'mail.google.com' => 'gmail',
        'open.spotify.com' => 'spotify',
        'api.whatsapp.com' => 'whatsapp',
        'store.steampowered.com' => 'steam',
        'instagram.com' => 'instagram',
        'instagr.am' => 'instagram',
        'x.com' => 'x',
        'twitter.com' => 'x',
        'tiktok.com' => 'tiktok',
        'youtube.com' => 'youtube',
        'youtu.be' => 'youtube',
        'facebook.com' => 'facebook',
        'fb.com' => 'facebook',
        'fb.me' => 'facebook',
        'm.me' => 'messenger',
        'messenger.com' => 'messenger',
        'github.com' => 'github',
        'twitch.tv' => 'twitch',
        'threads.net' => 'threads',
        'threads.com' => 'threads',
        'bsky.app' => 'bluesky',
        't.me' => 'telegram',
        'telegram.me' => 'telegram',
        'telegram.org' => 'telegram',
        'discord.gg' => 'discord',
        'discord.com' => 'discord',
        'pinterest.com' => 'pinterest',
        'pin.it' => 'pinterest',
        'snapchat.com' => 'snapchat',
        'spotify.com' => 'spotify',
        'spotify.link' => 'spotify',
        'behance.net' => 'behance',
        'dribbble.com' => 'dribbble',
        'medium.com' => 'medium',
        'reddit.com' => 'reddit',
        'mastodon.social' => 'mastodon',
        'wa.me' => 'whatsapp',
        'whatsapp.com' => 'whatsapp',
        'soundcloud.com' => 'soundcloud',
        'deezer.com' => 'deezer',
        'vimeo.com' => 'vimeo',
        'buymeacoffee.com' => 'buymeacoffee',
        'patreon.com' => 'patreon',
        'ko-fi.com' => 'kofi',
        'paypal.com' => 'paypal',
        'paypal.me' => 'paypal',
        'calendly.com' => 'calendly',
        'etsy.com' => 'etsy',
        'myshopify.com' => 'shopify',
        'shopify.com' => 'shopify',
        'substack.com' => 'substack',
        'kick.com' => 'kick',
        'apple.com' => 'apple',
        'notion.so' => 'notion',
        'notion.site' => 'notion',
        'signal.me' => 'signal',
        'signal.group' => 'signal',
        'line.me' => 'line',
        'viber.com' => 'viber',
        'sahibinden.com' => 'sahibinden',
        'letterboxd.com' => 'letterboxd',
        'goodreads.com' => 'goodreads',
        'steamcommunity.com' => 'steam',
        'playstation.com' => 'playstation',
        'udemy.com' => 'udemy',
        'coursera.org' => 'coursera',
        'zoom.us' => 'zoom',
        'linktr.ee' => 'linktree',
        'bandcamp.com' => 'bandcamp',
        'tidal.com' => 'tidal',
        'wordpress.com' => 'wordpress',
        'tumblr.com' => 'tumblr',
        'vk.com' => 'vk',
        'flickr.com' => 'flickr',
        'unsplash.com' => 'unsplash',
        'figma.com' => 'figma',
        'yelp.com' => 'yelp',
        'gumroad.com' => 'gumroad',
        'artstation.com' => 'artstation',
        'deviantart.com' => 'deviantart',
        'vsco.co' => 'vsco',
        'wattpad.com' => 'wattpad',
        'dev.to' => 'devto',
        'stackoverflow.com' => 'stackoverflow',
        'gitlab.com' => 'gitlab',
        'producthunt.com' => 'producthunt',
        'linkedin.com' => 'icon:briefcase',
        'lnkd.in' => 'icon:briefcase',
        'trendyol.com' => 'icon:shopping-cart',
        'hepsiburada.com' => 'icon:shopping-cart',
        'n11.com' => 'icon:shopping-cart',
    ];

    /** Brands that use many country domains (airbnb.com.tr, amazon.de...). */
    private const BRAND_LABELS = [
        'airbnb' => 'airbnb',
        'tripadvisor' => 'tripadvisor',
        'amazon' => 'icon:shopping-cart',
    ];

    /**
     * Icon for a URL: ['brand' => key] or ['icon' => name], or null when nothing is recognised.
     *
     * @return array{brand?: string, icon?: string}|null
     */
    public static function detect(?string $url): ?array
    {
        $url = trim((string) $url);

        if (str_starts_with($url, 'mailto:')) {
            return ['icon' => 'mail'];
        }

        if (str_starts_with($url, 'tel:')) {
            return ['icon' => 'phone'];
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^(www\.|m\.|mobile\.)/', '', $host);

        if ($host === '') {
            return null;
        }

        // google.com/maps/... links
        if (preg_match('/(^|\.)google\.[a-z.]+$/', $host) && str_starts_with((string) parse_url($url, PHP_URL_PATH), '/maps')) {
            return self::result('google_maps');
        }

        foreach (self::HOSTS as $suffix => $value) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return self::result($value);
            }
        }

        foreach (self::BRAND_LABELS as $label => $value) {
            if (preg_match('/(^|\.)'.preg_quote($label, '/').'\.[a-z]{2,3}(\.[a-z]{2})?$/', $host)) {
                return self::result($value);
            }
        }

        return null;
    }

    /**
     * Normalise a stored choice. Returns null for anything that is not a known option.
     */
    public static function normalizeChoice(?string $choice): ?string
    {
        $choice = trim((string) $choice);

        if ($choice === '' || $choice === 'none') {
            return $choice;
        }

        if (str_starts_with($choice, 'brand:') && Icons::brand(substr($choice, 6)) !== null) {
            return $choice;
        }

        if (str_starts_with($choice, 'icon:') && in_array(substr($choice, 5), self::UI, true)) {
            return $choice;
        }

        return null;
    }

    /**
     * What a button shows for a stored choice and destination.
     *
     * @return array{brand?: string, icon?: string}|null
     */
    public static function resolve(?string $choice, ?string $url): ?array
    {
        return match (true) {
            $choice === 'none' => null,
            is_string($choice) && str_starts_with($choice, 'brand:') => ['brand' => substr($choice, 6)],
            is_string($choice) && str_starts_with($choice, 'icon:') => ['icon' => substr($choice, 5)],
            default => self::detect($url),
        };
    }

    /** Brand keys with their display names, for the editor's picker. */
    public static function brandOptions(): array
    {
        $brands = json_decode((string) file_get_contents(resource_path('icons/brands.json')), true) ?: [];
        $options = array_map(fn ($b) => $b['title'], $brands);
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    private static function result(string $value): array
    {
        return str_starts_with($value, 'icon:') ? ['icon' => substr($value, 5)] : ['brand' => $value];
    }
}
