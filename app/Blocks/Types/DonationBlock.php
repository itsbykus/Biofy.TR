<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

/** Link to an external donation / support page. No payment is processed by the platform. */
class DonationBlock extends BlockType
{
    public const PLATFORMS = ['buymeacoffee', 'patreon', 'kofi', 'papara', 'paypal', 'github_sponsors', 'other'];

    public function key(): string
    {
        return 'donation';
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
        return 'heart';
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:200'],
            'platform' => ['nullable', 'in:'.implode(',', self::PLATFORMS)],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return [
            'url' => $this->url($data, 'url'),
            'title' => self::cleanText($data['title'] ?? '', 100),
            'description' => self::cleanText($data['description'] ?? '', 200),
            'platform' => $data['platform'] ?? 'other',
        ];
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return $presented['url'];
    }
}
