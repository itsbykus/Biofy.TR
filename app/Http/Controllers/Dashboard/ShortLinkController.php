<?php

namespace App\Http\Controllers\Dashboard;

use App\Exceptions\UserFacingException;
use App\Http\Controllers\Controller;
use App\Models\ShortLink;
use App\Services\Entitlements;
use App\Services\QrCodeService;
use App\Services\ShortLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** The user's short links (/s/code) with click counts and QR codes. */
class ShortLinkController extends Controller
{
    public function __construct(
        private readonly ShortLinkService $links,
        private readonly Entitlements $entitlements,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.shortlinks.index', [
            'links' => ShortLink::query()->where('user_id', $user->id)->latest('id')->paginate(20),
            'used' => ShortLink::query()->where('user_id', $user->id)->count(),
            'limit' => $this->entitlements->limit($user, Entitlements::MAX_SHORT_LINKS),
            'base' => rtrim((string) config('app.url'), '/').'/s/',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target_url' => ['required', 'string', 'max:2048'],
            'code' => ['nullable', 'string', 'max:40'],
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $link = $this->links->create($request->user(), $data['target_url'], $data['code'] ?? null, $data['title'] ?? null);
        } catch (UserFacingException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', __('shortlinks.created', ['url' => $link->displayUrl()]));
    }

    public function update(Request $request, ShortLink $link): RedirectResponse
    {
        $this->own($request, $link);
        $data = $request->validate([
            'target_url' => ['required', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        $this->links->update($link, $data['target_url'], $data['title'] ?? null);

        return back()->with('success', __('shortlinks.updated'));
    }

    public function destroy(Request $request, ShortLink $link): RedirectResponse
    {
        $this->own($request, $link);
        $link->delete();

        return back()->with('success', __('shortlinks.deleted'));
    }

    public function qr(Request $request, ShortLink $link, string $format, QrCodeService $qr): Response
    {
        $this->own($request, $link);

        return QrController::respond($qr, $link->shortUrl(), $format, 'qr-'.$link->code, '#111111', '#FFFFFF', $request->boolean('download'));
    }

    private function own(Request $request, ShortLink $link): void
    {
        abort_unless($link->user_id === $request->user()->id, 404);
    }
}
