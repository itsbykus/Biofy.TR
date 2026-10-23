<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\SafeUrl;
use Illuminate\Validation\ValidationException;

class ImageBlock extends BlockType
{
    public function key(): string
    {
        return 'image';
    }

    public function group(): string
    {
        return 'media';
    }

    public function icon(): string
    {
        return 'image';
    }

    public function rules(): array
    {
        return [
            'media_id' => ['required', 'integer'],
            'alt' => ['nullable', 'string', 'max:150'],
            'caption' => ['nullable', 'string', 'max:200'],
            'url' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        $url = null;

        if (! empty($data['url'])) {
            $url = SafeUrl::normalize($data['url']) ?? throw ValidationException::withMessages(['data.url' => __('validation.custom.url.unsafe')]);
        }

        return [
            'media_id' => $this->ownedMediaId($data['media_id'] ?? null, $owner, 'media_id'),
            'alt' => self::cleanText($data['alt'] ?? '', 150),
            'caption' => self::cleanText($data['caption'] ?? '', 200),
            'url' => $url,
        ];
    }

    public function present(array $data, array $media): array
    {
        $data['src'] = $media[$data['media_id'] ?? 0] ?? null;

        return $data;
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return $presented['url'] ?? null;
    }

    public function summary(array $data): string
    {
        return (string) ($data['caption'] ?: $data['alt'] ?: __('blocks.types.image.name'));
    }
}
