<?php

namespace App\Support;

/** Inline SVG icons generated at build time (scripts/build-icons.mjs). */
final class Icons
{
    private static ?array $ui = null;

    private static ?array $brands = null;

    public static function ui(string $name): string
    {
        self::$ui ??= json_decode((string) file_get_contents(resource_path('icons/ui.json')), true) ?: [];

        return self::$ui[$name] ?? self::$ui['square'] ?? '';
    }

    /** @return array{title: string, hex: string, path: string, evenodd?: bool}|null */
    public static function brand(string $key): ?array
    {
        self::$brands ??= json_decode((string) file_get_contents(resource_path('icons/brands.json')), true) ?: [];

        return self::$brands[$key] ?? null;
    }
}
