<?php

namespace App\Blocks;

use App\Models\MediaFile;
use App\Models\User;
use App\Support\SafeUrl;
use Illuminate\Validation\ValidationException;

/**
 * One content block kind. Each type owns its validation rules, normalisation,
 * public presentation and (optionally) a click target for tracked redirects.
 * Data arrives validated by rules(); normalize() may still reject it.
 */
abstract class BlockType
{
    abstract public function key(): string;

    /** Validation rules for the block's `data` array (keys are relative to `data.`). */
    abstract public function rules(): array;

    /** Visual group in the "add block" picker. */
    public function group(): string
    {
        return 'content';
    }

    /** Requires the `profile.advanced_blocks` feature. */
    public function isAdvanced(): bool
    {
        return false;
    }

    /** Counts toward the `profile.max_links` quota. */
    public function countsAsLink(): bool
    {
        return false;
    }

    public function isSchedulable(): bool
    {
        return true;
    }

    public function defaults(): array
    {
        return [];
    }

    /** Clean validated input. Throw ValidationException (keys prefixed with `data.`) on semantic errors. */
    public function normalize(array $data, User $owner): array
    {
        return $data;
    }

    /**
     * Data stored in the published snapshot. `$media` maps media id => public path.
     * Must only contain values that are safe to render (all hrefs already validated).
     */
    public function present(array $data, array $media): array
    {
        return $data;
    }

    /** Where a tracked click on this block (or one of its items) should redirect. */
    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return null;
    }

    /** Short text shown in the editor's block list. */
    public function summary(array $data): string
    {
        return (string) ($data['title'] ?? $data['text'] ?? $data['label'] ?? '');
    }

    public function name(): string
    {
        return __('blocks.types.'.$this->key().'.name');
    }

    public function description(): string
    {
        return __('blocks.types.'.$this->key().'.description');
    }

    public function icon(): string
    {
        return 'square';
    }

    protected function url(array $data, string $field): string
    {
        $url = SafeUrl::normalize($data[$field] ?? null);

        if ($url === null) {
            throw ValidationException::withMessages(['data.'.$field => __('validation.custom.url.unsafe')]);
        }

        return $url;
    }

    protected function ownedMediaId(mixed $id, User $owner, string $field): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $exists = MediaFile::query()->whereKey((int) $id)->where('user_id', $owner->id)->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['data.'.$field => __('validation.custom.media.not_owned')]);
        }

        return (int) $id;
    }

    protected static function cleanText(?string $value, int $max): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value);

        return mb_substr(trim($value), 0, $max);
    }
}
