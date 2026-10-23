<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

/** Renders the profile's social links (managed separately) as an icon row. */
class SocialIconsBlock extends BlockType
{
    public function key(): string
    {
        return 'social_icons';
    }

    public function group(): string
    {
        return 'links';
    }

    public function isSchedulable(): bool
    {
        return false;
    }

    public function icon(): string
    {
        return 'share-2';
    }

    public function rules(): array
    {
        return [
            'size' => ['nullable', 'in:sm,md,lg'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return ['size' => $data['size'] ?? 'md'];
    }

    public function summary(array $data): string
    {
        return __('blocks.types.social_icons.summary');
    }
}
