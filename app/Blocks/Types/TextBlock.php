<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use Illuminate\Support\Str;

/** Plain text only: rendered escaped with line breaks. No HTML or Markdown is interpreted. */
class TextBlock extends BlockType
{
    public function key(): string
    {
        return 'text';
    }

    public function icon(): string
    {
        return 'align-left';
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:2000'],
            'align' => ['nullable', 'in:left,center'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return [
            'text' => self::cleanText(str_replace("\r\n", "\n", $data['text'] ?? ''), 2000),
            'align' => $data['align'] ?? 'center',
        ];
    }

    public function summary(array $data): string
    {
        return Str::limit((string) ($data['text'] ?? ''), 60);
    }
}
