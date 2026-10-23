<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

class EmailBlock extends BlockType
{
    public function key(): string
    {
        return 'email';
    }

    public function group(): string
    {
        return 'contact';
    }

    public function countsAsLink(): bool
    {
        return true;
    }

    public function icon(): string
    {
        return 'mail';
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:191'],
            'label' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return [
            'email' => strtolower(trim($data['email'])),
            'label' => self::cleanText($data['label'] ?? '', 100),
            'subject' => self::cleanText($data['subject'] ?? '', 120),
        ];
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        $href = 'mailto:'.rawurlencode($presented['email']);
        $href = str_replace('%40', '@', $href);

        return $presented['subject'] ? $href.'?subject='.rawurlencode($presented['subject']) : $href;
    }

    public function summary(array $data): string
    {
        return (string) ($data['label'] ?: $data['email'] ?? '');
    }
}
