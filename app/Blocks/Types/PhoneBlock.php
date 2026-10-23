<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Validation\ValidationException;

class PhoneBlock extends BlockType
{
    public function key(): string
    {
        return 'phone';
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
        return 'phone';
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'label' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        $phone = Phone::normalize($data['phone'] ?? '') ?? throw ValidationException::withMessages(['data.phone' => __('validation.custom.phone.invalid')]);

        return ['phone' => $phone, 'label' => self::cleanText($data['label'] ?? '', 100)];
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return 'tel:'.$presented['phone'];
    }

    public function summary(array $data): string
    {
        return (string) ($data['label'] ?: $data['phone'] ?? '');
    }
}
