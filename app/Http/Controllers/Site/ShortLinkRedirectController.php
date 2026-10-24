<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ShortLink;
use App\Services\ShortLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** GET /s/{code}: counts the click and redirects (302, so every visit is counted). */
class ShortLinkRedirectController extends Controller
{
    public function __invoke(Request $request, string $code, ShortLinkService $links): RedirectResponse
    {
        $link = ShortLink::query()->with('user')->where('code', strtolower($code))->first();

        abort_if($link === null || ! $link->is_active || ! $link->user?->isActive(), 404);

        return redirect()->away($links->hit($link, $request), 302, [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Cache-Control' => 'no-store',
        ]);
    }
}
