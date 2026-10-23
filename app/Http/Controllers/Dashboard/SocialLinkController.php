<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\RespondsToEditor;
use App\Models\Profile;
use App\Models\ProfileSocialLink;
use App\Services\ProfileService;
use App\Services\SocialLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocialLinkController extends Controller
{
    use RespondsToEditor;

    private const FRAGMENTS = ['social-list' => 'dashboard.pages.partials.social-list'];

    public function __construct(
        private readonly SocialLinkService $social,
        private readonly ProfileService $profiles,
    ) {
    }

    public function store(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));
        $data = $request->validate([
            'platform' => ['required', Rule::in(array_keys(config('biofy.social_platforms')))],
            'value' => ['required', 'string', 'max:2048'],
        ]);

        $this->social->create($profile, $data['platform'], $data['value']);

        return $this->editorResponse($request, $profile, __('social.added'), 'social', self::FRAGMENTS);
    }

    public function update(Request $request, ProfileSocialLink $link): JsonResponse|RedirectResponse
    {
        $profile = $link->profile;
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));
        $data = $request->validate(['value' => ['required', 'string', 'max:2048']]);

        $this->social->update($link, $data['value']);

        return $this->editorResponse($request, $profile, __('social.updated'), 'social', self::FRAGMENTS);
    }

    public function destroy(Request $request, ProfileSocialLink $link): JsonResponse|RedirectResponse
    {
        $profile = $link->profile;
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $this->social->delete($link);

        return $this->editorResponse($request, $profile, __('social.deleted'), 'social', self::FRAGMENTS);
    }

    public function reorder(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));
        $request->validate(['order' => ['required', 'array', 'max:200'], 'order.*' => ['integer']]);

        $this->social->reorder($profile, $request->input('order'));

        return $this->editorResponse($request, $profile, __('blocks.reordered'), 'social', self::FRAGMENTS);
    }
}
