<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** QR code generator for any address or text, rendered on the server (no third-party API). */
class QrController extends Controller
{
    public function show(Request $request, QrCodeService $qr): View
    {
        $data = $this->input($request, false);

        return view('dashboard.qr.index', $data + [
            'preview' => $data['content'] !== '' ? $qr->svg($data['content'], 320, $data['fg'], $data['bg']) : null,
        ]);
    }

    public function download(Request $request, string $format, QrCodeService $qr): Response
    {
        $data = $this->input($request, true);

        return self::respond($qr, $data['content'], $format, 'qr-code', $data['fg'], $data['bg'], true);
    }

    /** Shared with the short links page. */
    public static function respond(QrCodeService $qr, string $content, string $format, string $name, string $fg, string $bg, bool $attachment): Response
    {
        if ($format === 'png') {
            return response($qr->png($content, 1024, $fg, $bg), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="'.$name.'.png"',
            ]);
        }

        return response($qr->svg($content, 512, $fg, $bg), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => ($attachment ? 'attachment' : 'inline').'; filename="'.$name.'.svg"',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
        ]);
    }

    /** @return array{content: string, fg: string, bg: string} */
    private function input(Request $request, bool $required): array
    {
        $data = $request->validate([
            'content' => [$required ? 'required' : 'nullable', 'string', 'max:'.QrCodeService::MAX_LENGTH],
            'fg' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $fg = strtoupper($data['fg'] ?? '#111111');
        $bg = strtoupper($data['bg'] ?? '#FFFFFF');

        if (! QrCodeService::readable($fg, $bg)) {
            throw ValidationException::withMessages(['fg' => __('qr.low_contrast')]);
        }

        return ['content' => trim((string) ($data['content'] ?? '')), 'fg' => $fg, 'bg' => $bg];
    }
}
