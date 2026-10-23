<?php

namespace App\Themes;

use App\Support\Icons;

/**
 * Schema + sanitiser for per-profile theme customisation. Every value that ends
 * up in the page's CSS is either a validated #RRGGBB colour, a clamped integer
 * or a value from a fixed list - user input can never inject CSS.
 */
class ThemeSettings
{
    /** 'basic' = available to every plan; 'advanced' = needs profile.advanced_customization. */
    public static function fields(): array
    {
        return [
            'mode' => ['type' => 'enum', 'options' => ['light', 'dark'], 'tier' => 'basic'],
            'bg_color' => ['type' => 'color', 'tier' => 'basic'],
            'text_color' => ['type' => 'color', 'tier' => 'basic'],
            'button_bg' => ['type' => 'color', 'tier' => 'basic'],
            'button_text' => ['type' => 'color', 'tier' => 'basic'],
            'bg_type' => ['type' => 'enum', 'options' => ['solid', 'gradient', 'image'], 'tier' => 'advanced'],
            'bg_gradient_from' => ['type' => 'color', 'tier' => 'advanced'],
            'bg_gradient_to' => ['type' => 'color', 'tier' => 'advanced'],
            'bg_gradient_angle' => ['type' => 'int', 'min' => 0, 'max' => 360, 'tier' => 'advanced'],
            'bg_image_media_id' => ['type' => 'media', 'tier' => 'advanced'],
            'bg_overlay' => ['type' => 'int', 'min' => 0, 'max' => 80, 'tier' => 'advanced'],
            'muted_color' => ['type' => 'color', 'tier' => 'basic'],
            'card_bg' => ['type' => 'color', 'tier' => 'advanced'],
            'button_style' => ['type' => 'enum', 'options' => ['fill', 'outline', 'soft', 'glass', 'underline'], 'tier' => 'basic'],
            'button_radius' => ['type' => 'int', 'min' => 0, 'max' => 999, 'tier' => 'basic'],
            'button_border_width' => ['type' => 'int', 'min' => 0, 'max' => 4, 'tier' => 'basic'],
            'button_border_color' => ['type' => 'color', 'tier' => 'basic'],
            'button_shadow' => ['type' => 'enum', 'options' => ['none', 'soft', 'hard', 'glow'], 'tier' => 'basic'],
            'card_style' => ['type' => 'enum', 'options' => ['flat', 'outlined', 'elevated', 'glass'], 'tier' => 'advanced'],
            'avatar_size' => ['type' => 'int', 'min' => 56, 'max' => 160, 'tier' => 'advanced'],
            'avatar_shape' => ['type' => 'enum', 'options' => ['circle', 'rounded', 'square'], 'tier' => 'advanced'],
            'banner_style' => ['type' => 'enum', 'options' => ['none', 'cover', 'contained'], 'tier' => 'advanced'],
            'font' => ['type' => 'enum', 'options' => array_keys(ThemeRegistry::FONTS), 'tier' => 'basic'],
            'heading_font' => ['type' => 'enum', 'options' => array_keys(ThemeRegistry::FONTS), 'tier' => 'advanced'],
            'font_size' => ['type' => 'int', 'min' => 14, 'max' => 19, 'tier' => 'advanced'],
            'icon_style' => ['type' => 'enum', 'options' => ['filled', 'outline', 'plain'], 'tier' => 'basic'],
            'icon_shape' => ['type' => 'enum', 'options' => ['circle', 'rounded', 'square'], 'tier' => 'basic'],
            // theme = text/button colours, brand = each platform's own colour, custom = icon_custom_color
            'icon_color' => ['type' => 'enum', 'options' => ['theme', 'brand', 'custom'], 'tier' => 'basic'],
            'icon_custom_color' => ['type' => 'color', 'tier' => 'basic'],
            'page_width' => ['type' => 'enum', 'options' => ['narrow', 'medium', 'wide'], 'tier' => 'advanced'],
            'align' => ['type' => 'enum', 'options' => ['center', 'left', 'right'], 'tier' => 'basic'],
            'spacing' => ['type' => 'enum', 'options' => ['compact', 'normal', 'relaxed'], 'tier' => 'advanced'],
            'animation' => ['type' => 'enum', 'options' => ['none', 'subtle', 'lively'], 'tier' => 'advanced'],
        ];
    }

    /**
     * Returns the sparse override array to store. Advanced keys are dropped (kept
     * from $current) when the user lacks advanced customisation.
     */
    public static function sanitize(array $input, bool $advancedAllowed, array $current = []): array
    {
        $result = [];

        foreach (self::fields() as $key => $field) {
            $allowed = $field['tier'] === 'basic' || $advancedAllowed;

            if (! $allowed) {
                if (array_key_exists($key, $current)) {
                    $result[$key] = $current[$key];
                }

                continue;
            }

            if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
                continue;
            }

            $value = self::cleanValue($field, $input[$key]);

            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** Full, render-ready settings: theme defaults + palette for the mode + user overrides. */
    public static function resolve(array $theme, ?array $overrides): array
    {
        $overrides = is_array($overrides) ? $overrides : [];
        $defaults = $theme['defaults'];
        $mode = $overrides['mode'] ?? $defaults['mode'];
        $palette = $theme['palettes'][$mode] ?? reset($theme['palettes']);
        $resolved = array_merge(
            ['bg_gradient_from' => $palette['bg_color'], 'bg_gradient_to' => $palette['bg_color'], 'bg_gradient_angle' => 180, 'bg_overlay' => 30, 'bg_image_media_id' => null,
                'icon_shape' => 'circle', 'icon_color' => 'theme', 'icon_custom_color' => $palette['text_color']],
            $defaults,
            $palette,
            ['mode' => $mode],
        );

        foreach ($overrides as $key => $value) {
            $field = self::fields()[$key] ?? null;

            if ($field && self::cleanValue($field, $value) !== null) {
                $resolved[$key] = self::cleanValue($field, $value);
            }
        }

        return $resolved;
    }

    public static function cleanValue(array $field, mixed $value): mixed
    {
        return match ($field['type']) {
            'color' => is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtoupper($value) : null,
            'int' => is_numeric($value) ? max($field['min'], min($field['max'], (int) $value)) : null,
            'enum' => in_array((string) $value, $field['options'], true) ? (string) $value : null,
            'media' => is_numeric($value) && (int) $value > 0 ? (int) $value : null,
            default => null,
        };
    }

    /** CSS custom properties for the resolved settings. Safe by construction (see class doc). */
    public static function cssVariables(array $s, ?string $backgroundImageUrl = null): string
    {
        $fonts = ThemeRegistry::FONTS;
        $vars = [
            '--bg' => $s['bg_color'],
            '--bg-from' => $s['bg_gradient_from'],
            '--bg-to' => $s['bg_gradient_to'],
            '--bg-angle' => ((int) $s['bg_gradient_angle']).'deg',
            '--bg-overlay' => round(((int) $s['bg_overlay']) / 100, 2),
            '--text' => $s['text_color'],
            '--muted' => $s['muted_color'],
            '--card' => $s['card_bg'],
            '--btn-bg' => $s['button_bg'],
            '--btn-text' => $s['button_text'],
            '--btn-border' => $s['button_border_color'],
            '--btn-border-w' => ((int) $s['button_border_width']).'px',
            '--btn-radius' => min(999, (int) $s['button_radius']).'px',
            '--avatar' => ((int) $s['avatar_size']).'px',
            '--font' => $fonts[$s['font']]['family'] ?? $fonts['inter']['family'],
            '--font-heading' => $fonts[$s['heading_font']]['family'] ?? $fonts['inter']['family'],
            '--font-size' => ((int) $s['font_size']).'px',
            '--icon-color' => $s['icon_custom_color'],
            '--icon-on' => self::onColor($s['icon_custom_color']),
        ];

        $css = '';

        foreach ($vars as $name => $value) {
            $css .= $name.':'.$value.';';
        }

        if ($backgroundImageUrl !== null && $s['bg_type'] === 'image') {
            // URL comes from our own media disk (random filename); quote-escaped defensively anyway.
            $css .= '--bg-image:url("'.str_replace(['"', '\\', "\n", ')'], '', $backgroundImageUrl).'");';
        }

        return $css;
    }

    /**
     * Per-platform icon colours for icon_color = brand, e.g. ".br-instagram{--ic-bg:#FF0069;...}".
     * Colours come from the bundled brand list (validated #RRGGBB) and platform keys from config,
     * so nothing user-supplied reaches the CSS. A brand colour that would be hard to see on the page
     * background is only used for filled icons; outline/plain icons then keep the text colour.
     */
    public static function socialBrandCss(array $s, array $platforms): string
    {
        if (($s['icon_color'] ?? 'theme') !== 'brand') {
            return '';
        }

        $css = '';

        foreach (array_unique($platforms) as $platform) {
            $hex = strtoupper((string) (Icons::brand($platform)['hex'] ?? ''));

            if (! preg_match('/^[a-z_]+$/', $platform) || ! preg_match('/^#[0-9A-F]{6}$/', $hex)) {
                continue;
            }

            $css .= '.br-'.$platform.'{--ic-bg:'.$hex.';--ic-on:'.self::onColor($hex).';'
                .(self::contrast($hex, $s['bg_color']) >= 1.8 ? '--ic-c:'.$hex.';' : '').'}';
        }

        return $css;
    }

    /** Dark or light foreground for text/icons placed on $hex. */
    public static function onColor(string $hex): string
    {
        return self::luminance($hex) > 0.45 ? '#111111' : '#FFFFFF';
    }

    private static function contrast(string $a, string $b): float
    {
        [$light, $dark] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return ($light + 0.05) / ($dark + 0.05);
    }

    /** WCAG relative luminance of a #RRGGBB colour. */
    private static function luminance(string $hex): float
    {
        $channels = array_map(function (string $pair) {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /** The font keys a page needs (to load only those font files). */
    public static function fontsUsed(array $s): array
    {
        return array_values(array_unique(array_filter([$s['font'] ?? null, $s['heading_font'] ?? null], fn ($f) => $f && $f !== 'system')));
    }
}
