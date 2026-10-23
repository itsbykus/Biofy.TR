<?php

namespace App\Http\Controllers\Dashboard;

use App\Blocks\BlockRegistry;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\RespondsToEditor;
use App\Models\Profile;
use App\Models\ProfileBlock;
use App\Services\BlockService;
use App\Services\Entitlements;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlockController extends Controller
{
    use RespondsToEditor;

    private const FRAGMENTS = ['blocks-list' => 'dashboard.pages.partials.blocks-list'];

    public function __construct(
        private readonly BlockService $blocks,
        private readonly ProfileService $profiles,
        private readonly BlockRegistry $registry,
        private readonly Entitlements $entitlements,
    ) {
    }

    /** Server-rendered form for a new block of the chosen type (loaded into the editor dialog). */
    public function create(Request $request, Profile $profile): View
    {
        $this->authorize('update', $profile);
        $type = (string) $request->query('type');
        abort_unless($this->registry->has($type), 404);

        return view('dashboard.pages.partials.block-form', $this->editorFragmentData($profile) + [
            'block' => null,
            'type' => $this->registry->get($type),
        ]);
    }

    public function store(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $this->blocks->create($profile, (string) $request->input('type'), $request->only(['data', 'starts_at', 'ends_at', 'position']));

        return $this->editorResponse($request, $profile, __('blocks.added'), 'content', self::FRAGMENTS);
    }

    public function edit(Request $request, ProfileBlock $block): View
    {
        $profile = $block->profile;
        $this->authorize('update', $profile);

        return view('dashboard.pages.partials.block-form', $this->editorFragmentData($profile) + [
            'block' => $block,
            'type' => $this->registry->get($block->type),
        ]);
    }

    public function update(Request $request, ProfileBlock $block): JsonResponse|RedirectResponse
    {
        $profile = $block->profile;
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $this->blocks->update($block, $request->only(['data', 'starts_at', 'ends_at']));

        return $this->editorResponse($request, $profile, __('blocks.updated'), 'content', self::FRAGMENTS);
    }

    public function visibility(Request $request, ProfileBlock $block): JsonResponse|RedirectResponse
    {
        $profile = $block->profile;
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $this->blocks->setVisibility($block, $request->boolean('visible'));

        return $this->editorResponse($request, $profile, $request->boolean('visible') ? __('blocks.shown') : __('blocks.hidden'), 'content', self::FRAGMENTS);
    }

    public function duplicate(Request $request, ProfileBlock $block): JsonResponse|RedirectResponse
    {
        $profile = $block->profile;
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $this->blocks->duplicate($block);

        return $this->editorResponse($request, $profile, __('blocks.duplicated'), 'content', self::FRAGMENTS);
    }

    public function destroy(Request $request, ProfileBlock $block): JsonResponse|RedirectResponse
    {
        $profile = $block->profile;
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $this->blocks->delete($block);

        return $this->editorResponse($request, $profile, __('blocks.deleted'), 'content', self::FRAGMENTS);
    }

    public function reorder(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));
        $request->validate(['order' => ['required', 'array', 'max:1000'], 'order.*' => ['integer']]);

        $this->blocks->reorder($profile, $request->input('order'));

        return $this->editorResponse($request, $profile, __('blocks.reordered'), 'content', self::FRAGMENTS);
    }
}
