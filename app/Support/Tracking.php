<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Third-party measurement tags (Google, Meta, TikTok). IDs are strictly validated, so only an
 * ID - never markup or code - reaches the page; the loader is our own script
 * (resources/js/tracking.js) and only runs after the visitor's consent.
 */
final class Tracking
{
    /** field => [provider, pattern, example] */
    public const FIELDS = [
        'ga4' => ['google', '/^G-[A-Z0-9]{4,16}$/', 'G-XXXXXXXXXX'],
        'google_ads' => ['google', '/^AW-\d{6,14}$/', 'AW-123456789'],
        'gtm' => ['google', '/^GTM-[A-Z0-9]{4,12}$/', 'GTM-XXXXXXX'],
        'meta' => ['meta', '/^\d{8,20}$/', '123456789012345'],
        'tiktok' => ['tiktok', '/^[A-Z0-9]{10,30}$/', 'CXXXXXXXXXXXXXXXXXXX'],
    ];

    /** Hosts each provider needs in the Content-Security-Policy (added only on pages using it). */
    public const CSP = [
        'google' => [
            'script-src' => ['https://www.googletagmanager.com'],
            'img-src' => ['https://www.googletagmanager.com', 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.g.doubleclick.net', 'https://www.google.com', 'https://*.google.com'],
            'connect-src' => ['https://www.googletagmanager.com', 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.g.doubleclick.net', 'https://www.google.com'],
            'frame-src' => ['https://www.googletagmanager.com', 'https://td.doubleclick.net'],
        ],
        'meta' => [
            'script-src' => ['https://connect.facebook.net'],
            'img-src' => ['https://www.facebook.com'],
            'connect-src' => ['https://www.facebook.com', 'https://connect.facebook.net'],
        ],
        'tiktok' => [
            'script-src' => ['https://analytics.tiktok.com'],
            'img-src' => ['https://analytics.tiktok.com'],
            'connect-src' => ['https://analytics.tiktok.com', 'https://*.tiktok.com'],
        ],
    ];

    public const PROVIDER_NAMES = ['google' => 'Google', 'meta' => 'Meta', 'tiktok' => 'TikTok'];

    /**
     * Clean user input; empty values are dropped. Throws with "{$prefix}.{field}" error keys.
     *
     * @return array<string, string>
     */
    public static function normalize(array $input, string $prefix = 'tracking'): array
    {
        $clean = [];

        foreach (self::FIELDS as $field => [, $pattern]) {
            $value = strtoupper(trim((string) ($input[$field] ?? '')));

            if ($value === '') {
                continue;
            }

            if (! preg_match($pattern, $value)) {
                throw ValidationException::withMessages([$prefix.'.'.$field => __('tracking.invalid', ['example' => self::FIELDS[$field][2]])]);
            }

            $clean[$field] = $value;
        }

        return $clean;
    }

    /** Only well-formed IDs (stored data is re-checked before it reaches a page). */
    public static function valid(?array $ids): array
    {
        $valid = [];

        foreach (self::FIELDS as $field => [, $pattern]) {
            $value = strtoupper(trim((string) ($ids[$field] ?? '')));

            if ($value !== '' && preg_match($pattern, $value)) {
                $valid[$field] = $value;
            }
        }

        return $valid;
    }

    /** @return list<string> providers used by these IDs */
    public static function providers(array $ids): array
    {
        return array_values(array_unique(array_map(fn ($field) => self::FIELDS[$field][0], array_keys(self::valid($ids)))));
    }

    public static function providerList(array $ids): string
    {
        return implode(', ', array_map(fn ($p) => self::PROVIDER_NAMES[$p], self::providers($ids)));
    }
}
