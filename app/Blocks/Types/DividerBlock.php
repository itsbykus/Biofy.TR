<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

class DividerBlock extends BlockType
{
    public function key(): string
    {
        return 'divider';
    }

    public function icon(): string
    {
        return 'minus';
    }

    public function isSchedulable(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'style' => ['nullable', 'in:line,dots,space'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return ['style' => $data['style'] ?? 'line'];
    }

    public function summary(array $data): string
    {
        return __('blocks.divider_styles.'.($data['style'] ?? 'line'));
    }
}
