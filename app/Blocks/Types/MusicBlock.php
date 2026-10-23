<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\Embeds;
use Illuminate\Validation\ValidationException;

/** Spotify, SoundCloud, Apple Music or Deezer player. */
class MusicBlock extends BlockType
{
    public function key(): string
    {
        return 'music';
    }

    public function group(): string
    {
        return 'media';
    }

    public function isAdvanced(): bool
    {
        return true;
    }

    public function icon(): string
    {
        return 'music';
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        $url = $this->url($data, 'url');
        $embed = Embeds::music($url) ?? throw ValidationException::withMessages(['data.url' => __('validation.custom.embed.music')]);

        return ['url' => $url, 'provider' => $embed['provider'], 'title' => self::cleanText($data['title'] ?? '', 100)];
    }

    public function present(array $data, array $media): array
    {
        $embed = Embeds::music($data['url']);
        $data['embed'] = $embed['embed'] ?? null;
        $data['height'] = $embed['height'] ?? 152;

        return $data;
    }

    public function summary(array $data): string
    {
        return (string) ($data['title'] ?: $data['url'] ?? '');
    }
}
