<?php

namespace App\Services;

use App\Blocks\BlockRegistry;
use App\Exceptions\UserFacingException;
use App\Models\Profile;
use App\Models\ProfileBlock;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Editor operations on a profile's draft blocks. Every entitlement is enforced
 * here (not only in the UI): advanced block types, block/link quotas, scheduling.
 */
class BlockService
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly Entitlements $entitlements,
    ) {
    }

    public function create(Profile $profile, string $type, array $input): ProfileBlock
    {
        $owner = $profile->user;
        $blockType = $this->resolveType($type, $owner);

        $existing = $profile->blocks()->count();

        if (! $this->entitlements->withinLimit($owner, Entitlements::MAX_BLOCKS, $existing)) {
            throw new UserFacingException(__('blocks.limit_blocks'));
        }

        if ($blockType->countsAsLink() && ! $this->entitlements->withinLimit($owner, Entitlements::MAX_LINKS, $this->linkCount($profile))) {
            throw new UserFacingException(__('blocks.limit_links'));
        }

        [$data, $schedule] = $this->validated($blockType, $input, $owner);

        $block = DB::transaction(function () use ($profile, $type, $data, $schedule, $input) {
            $position = (int) $profile->blocks()->max('position') + 1;

            if (($input['position'] ?? null) === 'top') {
                $profile->blocks()->increment('position');
                $position = 0;
            }

            return ProfileBlock::query()->create([
                'profile_id' => $profile->id,
                'type' => $type,
                'position' => $position,
                'is_visible' => true,
                'data' => $data,
                'starts_at' => $schedule['starts_at'],
                'ends_at' => $schedule['ends_at'],
            ]);
        });

        $profile->markDirty();

        return $block;
    }

    public function update(ProfileBlock $block, array $input): ProfileBlock
    {
        $profile = $block->profile;
        $blockType = $this->resolveType($block->type, $profile->user);
        [$data, $schedule] = $this->validated($blockType, $input, $profile->user);

        $block->fill(['data' => $data, 'starts_at' => $schedule['starts_at'], 'ends_at' => $schedule['ends_at']])->save();
        $profile->markDirty();

        return $block;
    }

    public function setVisibility(ProfileBlock $block, bool $visible): void
    {
        $block->forceFill(['is_visible' => $visible])->save();
        $block->profile->markDirty();
    }

    public function delete(ProfileBlock $block): void
    {
        $profile = $block->profile;
        $block->delete();
        $profile->markDirty();
    }

    public function duplicate(ProfileBlock $block): ProfileBlock
    {
        return $this->create($block->profile, $block->type, [
            'data' => $block->data,
            'starts_at' => $block->starts_at?->toDateTimeString(),
            'ends_at' => $block->ends_at?->toDateTimeString(),
        ]);
    }

    /** @param list<int> $orderedIds must contain exactly this profile's block ids */
    public function reorder(Profile $profile, array $orderedIds): void
    {
        $ids = array_map('intval', $orderedIds);
        $current = $profile->blocks()->pluck('id')->all();

        sort($current);
        $sorted = $ids;
        sort($sorted);

        if ($sorted !== $current) {
            throw ValidationException::withMessages(['order' => __('blocks.reorder_mismatch')]);
        }

        DB::transaction(function () use ($ids, $profile) {
            foreach ($ids as $position => $id) {
                ProfileBlock::query()->whereKey($id)->where('profile_id', $profile->id)->update(['position' => $position]);
            }
        });

        $profile->markDirty();
    }

    public function linkCount(Profile $profile): int
    {
        return $profile->blocks()->whereIn('type', $this->registry->linkTypes())->count();
    }

    private function resolveType(string $type, User $owner): \App\Blocks\BlockType
    {
        if (! $this->registry->has($type)) {
            throw ValidationException::withMessages(['type' => __('blocks.unknown_type')]);
        }

        $blockType = $this->registry->get($type);

        if ($blockType->isAdvanced() && ! $this->entitlements->allows($owner, Entitlements::ADVANCED_BLOCKS)) {
            throw new UserFacingException(__('blocks.requires_upgrade'));
        }

        return $blockType;
    }

    /** @return array{0: array, 1: array{starts_at: ?Carbon, ends_at: ?Carbon}} */
    private function validated(\App\Blocks\BlockType $type, array $input, User $owner): array
    {
        $rules = ['data' => ['required', 'array']];

        foreach ($type->rules() as $field => $fieldRules) {
            $rules['data.'.$field] = $fieldRules;
        }

        $rules['starts_at'] = ['nullable', 'date'];
        $rules['ends_at'] = ['nullable', 'date', 'after:starts_at'];

        $input['data'] = array_merge($type->defaults(), (array) ($input['data'] ?? []));
        $validated = Validator::make($input, $rules, [], $this->attributeNames($type))->validate();

        $data = $type->normalize($validated['data'], $owner);

        $schedule = ['starts_at' => null, 'ends_at' => null];
        $wantsSchedule = ! empty($validated['starts_at']) || ! empty($validated['ends_at']);

        if ($wantsSchedule) {
            if (! $type->isSchedulable()) {
                throw ValidationException::withMessages(['starts_at' => __('blocks.not_schedulable')]);
            }

            if (! $this->entitlements->allows($owner, Entitlements::SCHEDULED_LINKS)) {
                throw new UserFacingException(__('blocks.scheduling_requires_upgrade'));
            }

            // Inputs are wall-clock times in the user's (or site's) time zone; stored in UTC.
            $tz = $owner->timezone ?: app(Settings::class)->get('site.timezone');
            $schedule['starts_at'] = ! empty($validated['starts_at']) ? Carbon::parse($validated['starts_at'], $tz)->utc() : null;
            $schedule['ends_at'] = ! empty($validated['ends_at']) ? Carbon::parse($validated['ends_at'], $tz)->utc() : null;
        }

        return [$data, $schedule];
    }

    private function attributeNames(\App\Blocks\BlockType $type): array
    {
        $names = [];

        foreach (array_keys($type->rules()) as $field) {
            $base = preg_replace('/^items\.\*\./', '', $field);
            $names['data.'.$field] = __('blocks.fields.'.$base);
        }

        return $names;
    }
}
