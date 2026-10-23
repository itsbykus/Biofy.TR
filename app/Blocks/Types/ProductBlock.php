<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\SafeUrl;
use Illuminate\Validation\ValidationException;

/** Product / service cards. Prices are display text only; nothing is sold through the platform. */
class ProductBlock extends BlockType
{
    public function key(): string
    {
        return 'product';
    }

    public function group(): string
    {
        return 'commerce';
    }

    public function isAdvanced(): bool
    {
        return true;
    }

    public function countsAsLink(): bool
    {
        return true;
    }

    public function icon(): string
    {
        return 'shopping-bag';
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'layout' => ['nullable', 'in:list,grid'],
            'items' => ['required', 'array', 'min:1', 'max:12'],
            'items.*.title' => ['required', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:300'],
            'items.*.price' => ['nullable', 'string', 'max:40'],
            'items.*.url' => ['nullable', 'string', 'max:2048'],
            'items.*.media_id' => ['nullable', 'integer'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        $items = [];

        foreach (array_values($data['items'] ?? []) as $i => $item) {
            $url = null;

            if (! empty($item['url'])) {
                $url = SafeUrl::normalize($item['url']) ?? throw ValidationException::withMessages(["data.items.$i.url" => __('validation.custom.url.unsafe')]);
            }

            $items[] = [
                'title' => self::cleanText($item['title'] ?? '', 100),
                'description' => self::cleanText($item['description'] ?? '', 300),
                'price' => self::cleanText($item['price'] ?? '', 40),
                'url' => $url,
                'media_id' => $this->ownedMediaId($item['media_id'] ?? null, $owner, "items.$i.media_id"),
            ];
        }

        return ['title' => self::cleanText($data['title'] ?? '', 100), 'layout' => $data['layout'] ?? 'list', 'items' => $items];
    }

    public function present(array $data, array $media): array
    {
        foreach ($data['items'] as &$item) {
            $item['image'] = $item['media_id'] ? ($media[$item['media_id']] ?? null) : null;
        }

        return $data;
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return $item !== null ? ($presented['items'][$item]['url'] ?? null) : null;
    }

    public function summary(array $data): string
    {
        return (string) ($data['title'] ?: trans_choice('blocks.items_count', count($data['items'] ?? []), ['count' => count($data['items'] ?? [])]));
    }
}
