<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;

/** Booking / calendar link (Calendly, Cal.com, Google Calendar appointment pages...). */
class CalendarBlock extends BlockType
{
    public function key(): string
    {
        return 'calendar';
    }

    public function group(): string
    {
        return 'contact';
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
        return 'calendar';
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        return [
            'url' => $this->url($data, 'url'),
            'title' => self::cleanText($data['title'] ?? '', 100),
            'description' => self::cleanText($data['description'] ?? '', 200),
        ];
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        return $presented['url'];
    }
}
