<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** QR codes rendered locally (no third-party QR API ever sees the user's URLs or 2FA secrets). */
class QrCodeService
{
    public const MAX_LENGTH = 1200;

    /** $foreground / $background: #RRGGBB. */
    public function svg(string $content, int $size = 280, string $foreground = '#111111', string $background = '#FFFFFF'): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2, null, null, $this->fill($foreground, $background)),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($content);

        // Drop the XML prolog so the markup can be inlined into HTML.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }

    public function png(string $content, int $size = 1024, string $foreground = '#000000', string $background = '#FFFFFF'): string
    {
        $renderer = new GDLibRenderer($size, 4, 'png', 9, $this->fill($foreground, $background));

        return (new Writer($renderer))->writeString($content);
    }

    /** True when the two colours differ enough for phone cameras to read the code. */
    public static function readable(string $foreground, string $background): bool
    {
        $luminance = function (string $hex): float {
            [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
            $lin = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

            return 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
        };

        $a = $luminance($foreground);
        $b = $luminance($background);

        // Dark modules on a light background, with WCAG-like contrast.
        return $a < $b && ($b + 0.05) / ($a + 0.05) >= 3;
    }

    private function fill(string $foreground, string $background): Fill
    {
        return Fill::uniformColor($this->rgb($background), $this->rgb($foreground));
    }

    private function rgb(string $hex): Rgb
    {
        $hex = preg_match('/^#[0-9a-f]{6}$/i', $hex) ? ltrim($hex, '#') : '000000';

        return new Rgb(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }
}
