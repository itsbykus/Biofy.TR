<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

class LinkBlock extends BlockType
{
    public function key(): string
    {
        return 'link';
    }

    public function group(): string
    {
        return 'links';
    }

    public function countsAsLink(): bool
    {
        return true;
    }

    public function icon(): string
    {
        return 'link';
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:200'],
            'media_id' => ['nullable', 'integer'],
            'new_tab' => ['nullable', 'boolean'],
            'highlight' => ['nullable', 'boolean'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return [
            'url' => $this->url($data, 'url'),
            'title' => self::cleanText($data['title'] ?? '', 100),
            'description' => self::cleanText($data['description'] ?? '', 200),
            'media_id' => $this->ownedMediaId($data['media_id'] ?? null, $owner, 'media_id'),
            'new_tab' => (bool) ($data['new_tab'] ?? true),
            'highlight' => (bool) ($data['highlight'] ?? false),
        ];
    }

    public function present(array $data, array $media): array
    {
        $data['thumbnail'] = isset($data['media_id']) ? ($media[$data['media_id']] ?? null) : null;

        return $data;
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return $presented['url'] ?? null;
    }
}
