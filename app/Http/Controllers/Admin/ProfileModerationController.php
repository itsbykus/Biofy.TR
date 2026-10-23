<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\AuditLogger;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileModerationController extends Controller
{
    public function index(Request $request): View
    {
        $profiles = Profile::query()->with('user:id,name,username,email,status')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $request->query('q')).'%';
                $q->where(fn ($w) => $w->where('slug', 'like', $term)->orWhere('display_name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest('updated_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.profiles.index', ['profiles' => $profiles]);
    }

    public function updateStatus(Request $request, Profile $profile, UserAccountService $accounts): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Profile::STATUS_DRAFT, Profile::STATUS_PUBLISHED, Profile::STATUS_SUSPENDED, Profile::STATUS_ARCHIVED])],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:status,suspended'],
        ]);

        $accounts->setProfileStatus($profile, $data['status'], $data['reason'] ?? null, $request->user());

        return back()->with('success', __('admin.profiles.status_updated'));
    }

    public function toggleVerified(Request $request, Profile $profile, AuditLogger $audit): RedirectResponse
    {
        $profile->forceFill(['verified_at' => $profile->verified_at ? null : now()])->save();
        $audit->log($profile->verified_at ? 'profile.verified' : 'profile.unverified', $profile);

        return back()->with('success', __('admin.profiles.verified_updated'));
    }
}
