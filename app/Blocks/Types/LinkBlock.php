<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\LinkIcons;
use Illuminate\Validation\ValidationException;

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
            // '' = recognised from the URL, 'none', 'brand:<key>' or 'icon:<name>' (see LinkIcons).
            'icon' => ['nullable', 'string', 'max:60'],
            'bg_media_id' => ['nullable', 'integer'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        $icon = LinkIcons::normalizeChoice($data['icon'] ?? '');

        if ($icon === null) {
            throw ValidationException::withMessages(['data.icon' => __('validation.in', ['attribute' => __('blocks.fields.icon')])]);
        }

        return [
            'url' => $this->url($data, 'url'),
            'title' => self::cleanText($data['title'] ?? '', 100),
            'description' => self::cleanText($data['description'] ?? '', 200),
            'media_id' => $this->ownedMediaId($data['media_id'] ?? null, $owner, 'media_id'),
            'new_tab' => (bool) ($data['new_tab'] ?? true),
            'highlight' => (bool) ($data['highlight'] ?? false),
            'icon' => $icon,
            'bg_media_id' => $this->ownedMediaId($data['bg_media_id'] ?? null, $owner, 'bg_media_id'),
        ];
    }

    public function present(array $data, array $media): array
    {
        $data['thumbnail'] = isset($data['media_id']) ? ($media[$data['media_id']] ?? null) : null;
        $data['background'] = isset($data['bg_media_id']) ? ($media[$data['bg_media_id']] ?? null) : null;
        $data['icon_view'] = LinkIcons::resolve($data['icon'] ?? '', $data['url'] ?? null);

        return $data;
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return $presented['url'] ?? null;
    }
}
