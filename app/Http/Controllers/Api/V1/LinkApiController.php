<?php

namespace App\Http\Controllers\Api\V1;

use App\Blocks\BlockRegistry;
use App\Models\Profile;
use App\Models\ProfileBlock;
use App\Services\BlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Links" are the link-type blocks of a page (link, email, phone, whatsapp, ...). */
class LinkApiController extends ApiController
{
    public function __construct(
        private readonly BlockService $blocks,
        private readonly BlockRegistry $registry,
    ) {
    }

    public function index(Request $request, int $profile): JsonResponse
    {
        $profile = $this->ownedProfile($request, $profile);

        $page = ProfileBlock::query()->where('profile_id', $profile->id)
            ->whereIn('type', $this->registry->linkTypes())
            ->orderBy('position')->paginate($this->perPage($request));

        return $this->paginated($page, [Presenters::class, 'block']);
    }

    public function store(Request $request, int $profile): JsonResponse
    {
        $profile = $this->ownedProfile($request, $profile);
        abort_if($profile->status === Profile::STATUS_SUSPENDED, 403);
        $request->validate(['type' => ['nullable', Rule::in($this->registry->linkTypes())]]);

        $block = $this->blocks->create($profile, (string) $request->input('type', 'link'), $request->only(['data', 'starts_at', 'ends_at', 'position']));

        return $this->data(Presenters::block($block->fresh()), 201);
    }

    public function update(Request $request, int $link): JsonResponse
    {
        $block = $this->ownedLink($request, $link);
        $input = $request->only(['data', 'starts_at', 'ends_at']);
        // PATCH semantics: unspecified data fields keep their current value.
        $input['data'] = array_merge($block->data ?? [], (array) $request->input('data', []));

        $block = $this->blocks->update($block, $input);

        if ($request->has('visible')) {
            $this->blocks->setVisibility($block, $request->boolean('visible'));
        }

        return $this->data(Presenters::block($block->fresh()));
    }

    public function destroy(Request $request, int $link): JsonResponse
    {
        $this->blocks->delete($this->ownedLink($request, $link));

        return response()->json(null, 204);
    }

    private function ownedLink(Request $request, int $id): ProfileBlock
    {
        $block = ProfileBlock::query()->whereKey($id)
            ->whereIn('type', $this->registry->linkTypes())
            ->whereHas('profile', fn ($q) => $q->where('user_id', $request->user()->id)->where('status', '!=', Profile::STATUS_SUSPENDED))
            ->first();

        abort_if($block === null, 404);

        return $block;
    }
}
