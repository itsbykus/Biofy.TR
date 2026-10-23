<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

class FaqBlock extends BlockType
{
    public function key(): string
    {
        return 'faq';
    }

    public function isAdvanced(): bool
    {
        return true;
    }

    public function icon(): string
    {
        return 'help-circle';
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.q' => ['required', 'string', 'max:200'],
            'items.*.a' => ['required', 'string', 'max:1500'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return [
            'title' => self::cleanText($data['title'] ?? '', 100),
            'items' => array_values(array_map(fn ($item) => [
                'q' => self::cleanText($item['q'] ?? '', 200),
                'a' => self::cleanText(str_replace("\r\n", "\n", $item['a'] ?? ''), 1500),
            ], $data['items'] ?? [])),
        ];
    }

    public function summary(array $data): string
    {
        return (string) ($data['title'] ?: trans_choice('blocks.items_count', count($data['items'] ?? []), ['count' => count($data['items'] ?? [])]));
    }
}
