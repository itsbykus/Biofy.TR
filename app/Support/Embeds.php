<?php

namespace App\Support;

/**
 * Parses provider URLs into IDs and rebuilds embed URLs ourselves. User input is
 * never used as an iframe src directly, so only allow-listed hosts can be framed.
 */
final class Embeds
{
    public static function youtubeId(?string $url): ?string
    {
        $url = SafeUrl::normalize($url);

        if (! $url || ! SafeUrl::hostMatches($url, ['youtube.com', 'youtu.be', 'youtube-nocookie.com'])) {
            return null;
        }

        $host = SafeUrl::host($url);
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $candidate = match (true) {
            $host === 'youtu.be' => ltrim($path, '/'),
            isset($query['v']) && is_string($query['v']) => $query['v'],
            (bool) preg_match('#^/(embed|shorts|live|v)/([^/?]+)#', $path, $m) => $m[2],
            default => null,
        };

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }

    public static function youtubeEmbed(string $id): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0';
    }

    public static function vimeoId(?string $url): ?string
    {
        $url = SafeUrl::normalize($url);

        if (! $url || ! SafeUrl::hostMatches($url, ['vimeo.com'])) {
            return null;
        }

        return preg_match('#/(?:video/)?(\d{5,12})(?:$|[/?#])#', (string) parse_url($url, PHP_URL_PATH).'/', $m) ? $m[1] : null;
    }

    public static function vimeoEmbed(string $id): string
    {
        return 'https://player.vimeo.com/video/'.$id.'?dnt=1';
    }

    /** @return array{provider: string, embed: string, height: int}|null */
    public static function music(?string $url): ?array
    {
        $url = SafeUrl::normalize($url);

        if (! $url) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        if (SafeUrl::hostMatches($url, ['open.spotify.com'])
            && preg_match('#^/(?:intl-[a-z]{2}(?:-[a-z]{2})?/)?(track|album|playlist|episode|show|artist)/([A-Za-z0-9]{22})#', $path, $m)) {
            $compact = in_array($m[1], ['track', 'episode'], true);

            return ['provider' => 'spotify', 'embed' => "https://open.spotify.com/embed/{$m[1]}/{$m[2]}", 'height' => $compact ? 152 : 352];
        }

        if (SafeUrl::hostMatches($url, ['soundcloud.com']) && SafeUrl::host($url) !== 'w.soundcloud.com'
            && preg_match('#^/[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+){0,2}/?$#', $path)) {
            $canonical = 'https://soundcloud.com'.rtrim($path, '/');

            return ['provider' => 'soundcloud', 'embed' => 'https://w.soundcloud.com/player/?url='.rawurlencode($canonical).'&visual=false&show_comments=false', 'height' => 166];
        }

        if (SafeUrl::host($url) === 'music.apple.com'
            && preg_match('#^/[a-z]{2}/(album|playlist|song)/[A-Za-z0-9%._-]+/[A-Za-z0-9.]+$#', $path)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $suffix = isset($query['i']) && ctype_digit((string) $query['i']) ? '?i='.$query['i'] : '';

            return ['provider' => 'apple_music', 'embed' => 'https://embed.music.apple.com'.$path.$suffix, 'height' => $suffix || $m[1] === 'song' ? 175 : 450];
        }

        if (SafeUrl::hostMatches($url, ['deezer.com'])
            && preg_match('#^/(?:[a-z]{2}/)?(track|album|playlist)/(\d+)#', $path, $m)) {
            return ['provider' => 'deezer', 'embed' => "https://widget.deezer.com/widget/auto/{$m[1]}/{$m[2]}", 'height' => $m[1] === 'track' ? 150 : 300];
        }

        return null;
    }

    public static function openStreetMap(float $lat, float $lon): string
    {
        $d = 0.01;

        return sprintf(
            'https://www.openstreetmap.org/export/embed.html?bbox=%F%%2C%F%%2C%F%%2C%F&layer=mapnik&marker=%F%%2C%F',
            $lon - $d, $lat - $d, $lon + $d, $lat + $d, $lat, $lon,
        );
    }

    /** Hosts allowed in frame-src of the Content-Security-Policy on profile pages. */
    public static function frameHosts(): array
    {
        return array_merge(...array_values(config('biofy.embed_hosts')));
    }
}
