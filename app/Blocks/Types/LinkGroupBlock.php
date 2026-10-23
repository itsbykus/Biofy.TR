<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\SafeUrl;
use Illuminate\Validation\ValidationException;

/** A titled, optionally collapsible group of links. Each item is tracked separately. */
class LinkGroupBlock extends BlockType
{
    public function key(): string
    {
        return 'link_group';
    }

    public function group(): string
    {
        return 'links';
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
        return 'folder';
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'collapsed' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.title' => ['required', 'string', 'max:100'],
            'items.*.url' => ['required', 'string', 'max:2048'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        $items = [];

        foreach (array_values($data['items'] ?? []) as $i => $item) {
            $url = SafeUrl::normalize($item['url'] ?? null) ?? throw ValidationException::withMessages(["data.items.$i.url" => __('validation.custom.url.unsafe')]);
            $items[] = ['title' => self::cleanText($item['title'] ?? '', 100), 'url' => $url];
        }

        return ['title' => self::cleanText($data['title'] ?? '', 100), 'collapsed' => (bool) ($data['collapsed'] ?? false), 'items' => $items];
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return $item !== null ? ($presented['items'][$item]['url'] ?? null) : null;
    }
}
