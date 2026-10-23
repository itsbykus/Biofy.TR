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
    public function svg(string $content, int $size = 280): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(17, 17, 17))),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($content);

        // Drop the XML prolog so the markup can be inlined into HTML.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }

    public function png(string $content, int $size = 1024): string
    {
        $renderer = new GDLibRenderer($size, 4);

        return (new Writer($renderer))->writeString($content);
    }
}
