<?php

namespace App\Support;

/** Coarse, privacy-friendly user-agent classification (no fingerprinting). */
final class UserAgent
{
    public static function isBot(?string $ua): bool
    {
        $ua = trim((string) $ua);

        return $ua === '' || strlen($ua) < 12 || (bool) preg_match(config('biofy.analytics.bot_pattern'), $ua);
    }

    public static function device(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua) => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|Opera Mini/i', $ua) => 'mobile',
            (bool) preg_match('/Windows|Macintosh|X11|Linux|CrOS/i', $ua) => 'desktop',
            default => 'other',
        };
    }

    public static function browser(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            str_contains($ua, 'Instagram') => 'instagram',
            str_contains($ua, 'FBAN') || str_contains($ua, 'FBAV') => 'facebook',
            str_contains($ua, 'TikTok') || str_contains($ua, 'musical_ly') => 'tiktok',
            str_contains($ua, 'SamsungBrowser') => 'samsung',
            str_contains($ua, 'Edg/') || str_contains($ua, 'EdgA/') || str_contains($ua, 'EdgiOS/') => 'edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'opera',
            str_contains($ua, 'YaBrowser') => 'yandex',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/') => 'firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'chrome',
            str_contains($ua, 'Safari/') => 'safari',
            default => 'other',
        };
    }

    public static function describe(?string $ua): string
    {
        $browser = self::browser($ua);
        $os = match (true) {
            (bool) preg_match('/iPhone|iPad|iPod/', (string) $ua) => 'iOS',
            str_contains((string) $ua, 'Android') => 'Android',
            str_contains((string) $ua, 'Windows') => 'Windows',
            str_contains((string) $ua, 'Mac OS X') => 'macOS',
            str_contains((string) $ua, 'Linux') => 'Linux',
            default => '?',
        };

        return ucfirst($browser).' · '.$os;
    }
}
