<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\BlockService;
use App\Services\Entitlements;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Shows the assigned package and limits. There is no billing in v1, so nothing about payments is shown. */
class PlanController extends Controller
{
    public function show(Request $request, Entitlements $entitlements, MediaService $media, BlockService $blocks): View
    {
        $user = $request->user();
        $profiles = $user->profiles()->where('status', '!=', Profile::STATUS_ARCHIVED)->get();

        return view('dashboard.plan.show', [
            'assignment' => $entitlements->currentAssignment($user),
            'package' => $entitlements->currentPackage($user),
            'features' => $entitlements->resolve($user),
            'usage' => [
                'profile.max_pages' => $profiles->count(),
                'profile.max_links' => $profiles->map(fn ($p) => $blocks->linkCount($p))->max() ?? 0,
                'profile.max_social_links' => $profiles->map(fn ($p) => $p->socialLinks()->count())->max() ?? 0,
                'profile.max_blocks' => $profiles->map(fn ($p) => $p->blocks()->count())->max() ?? 0,
                'storage.quota_mb' => round($media->usedBytes($user) / 1024 / 1024, 1),
                'domain.max_domains' => $user->customDomains()->count(),
                'api.max_tokens' => $user->tokens()->count(),
            ],
            'history' => $user->packageAssignments()->with('package')->latest('starts_at')->get(),
        ]);
    }
}
