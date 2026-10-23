<?php

namespace App\Support;

/**
 * Normalises user-supplied web URLs and rejects anything that is not a plain
 * http(s) URL: javascript:, data:, vbscript:, file:, protocol-relative URLs,
 * embedded credentials, control characters and whitespace are all refused.
 */
final class SafeUrl
{
    public const MAX_LENGTH = 2048;

    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $url = trim($input);

        if ($url === '' || strlen($url) > self::MAX_LENGTH) {
            return null;
        }

        // Control characters / whitespace inside a URL are a classic filter-bypass vector.
        if (preg_match('/[\x00-\x20\x7F]/', $url)) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            // "example.com/page" -> "https://example.com/page"
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower($parts['host']);

        if (! self::isValidHost($host)) {
            return null;
        }

        $rest = substr($url, strlen($parts['scheme']) + 3 + strlen($parts['host']));

        return $scheme.'://'.$host.$rest;
    }

    public static function isValid(?string $input): bool
    {
        return self::normalize($input) !== null;
    }

    public static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : null;
    }

    /** True when $url's host equals one of $domains or is a subdomain of one. */
    public static function hostMatches(string $url, array $domains): bool
    {
        $host = self::host($url);

        if ($host === null) {
            return false;
        }

        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    public static function isValidHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (strlen($host) > 253 || ! str_contains($host, '.')) {
            return false;
        }

        foreach (explode('.', $host) as $label) {
            if (! preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)$/', $label)) {
                return false;
            }
        }

        return true;
    }
}
