<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

class HeadingBlock extends BlockType
{
    public function key(): string
    {
        return 'heading';
    }

    public function icon(): string
    {
        return 'heading';
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:120'],
            'size' => ['nullable', 'in:md,lg'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return [
            'text' => self::cleanText($data['text'] ?? '', 120),
            'size' => $data['size'] ?? 'md',
        ];
    }
}
