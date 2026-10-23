<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

class GalleryBlock extends BlockType
{
    public function key(): string
    {
        return 'gallery';
    }

    public function group(): string
    {
        return 'media';
    }

    public function isAdvanced(): bool
    {
        return true;
    }

    public function icon(): string
    {
        return 'images';
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'columns' => ['nullable', 'in:2,3'],
            'items' => ['required', 'array', 'min:1', 'max:12'],
            'items.*.media_id' => ['required', 'integer'],
            'items.*.alt' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        $items = [];

        foreach (array_values($data['items'] ?? []) as $i => $item) {
            $items[] = [
                'media_id' => $this->ownedMediaId($item['media_id'] ?? null, $owner, "items.$i.media_id"),
                'alt' => self::cleanText($item['alt'] ?? '', 150),
            ];
        }

        return ['title' => self::cleanText($data['title'] ?? '', 100), 'columns' => (int) ($data['columns'] ?? 2), 'items' => $items];
    }

    public function present(array $data, array $media): array
    {
        $data['items'] = array_values(array_filter(array_map(function ($item) use ($media) {
            $item['src'] = $media[$item['media_id']] ?? null;

            return $item;
        }, $data['items']), fn ($item) => $item['src'] !== null));

        return $data;
    }

    public function summary(array $data): string
    {
        return (string) ($data['title'] ?: trans_choice('blocks.images_count', count($data['items'] ?? []), ['count' => count($data['items'] ?? [])]));
    }
}
