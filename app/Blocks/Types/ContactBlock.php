<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

/** "Save contact" button: serves a vCard built from the profile's published contact details. */
class ContactBlock extends BlockType
{
    public function key(): string
    {
        return 'contact';
    }

    public function group(): string
    {
        return 'contact';
    }

    public function isAdvanced(): bool
    {
        return true;
    }

    public function icon(): string
    {
        return 'contact';
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return ['label' => self::cleanText($data['label'] ?? '', 100)];
    }

    public function summary(array $data): string
    {
        return (string) ($data['label'] ?: __('blocks.types.contact.default_label'));
    }
}
