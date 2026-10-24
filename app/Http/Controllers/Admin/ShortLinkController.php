<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShortLink;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Moderation of users' short links (switch off abusive ones, delete). */
class ShortLinkController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        // LIKE wildcards typed by the admin are matched literally.
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

        $links = ShortLink::query()->with('user:id,name,username,email')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('code', 'like', strtolower($like))
                ->orWhere('target_url', 'like', $like)
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', $like))))
            ->latest('id')->paginate(30)->withQueryString();

        return view('admin.shortlinks.index', ['links' => $links, 'search' => $search]);
    }

    public function toggle(ShortLink $link): RedirectResponse
    {
        $link->forceFill(['is_active' => ! $link->is_active])->save();
        $this->audit->log($link->is_active ? 'shortlink.enabled' : 'shortlink.disabled', $link, ['code' => $link->code, 'target' => $link->target_url]);

        return back()->with('success', __('admin.saved'));
    }

    public function destroy(ShortLink $link): RedirectResponse
    {
        $this->audit->log('shortlink.deleted', $link, ['code' => $link->code, 'target' => $link->target_url]);
        $link->delete();

        return back()->with('success', __('admin.deleted'));
    }
}
