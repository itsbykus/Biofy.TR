<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\Embeds;
use Illuminate\Validation\ValidationException;

class YoutubeBlock extends BlockType
{
    public function key(): string
    {
        return 'youtube';
    }

    public function group(): string
    {
        return 'media';
    }

    public function icon(): string
    {
        return 'youtube';
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
        $id = Embeds::youtubeId($url) ?? throw ValidationException::withMessages(['data.url' => __('validation.custom.embed.youtube')]);

        return ['url' => $url, 'video_id' => $id, 'title' => self::cleanText($data['title'] ?? '', 100)];
    }

    public function present(array $data, array $media): array
    {
        $data['embed'] = Embeds::youtubeEmbed($data['video_id']);
        $data['thumbnail'] = 'https://i.ytimg.com/vi/'.$data['video_id'].'/hqdefault.jpg';

        return $data;
    }

    public function summary(array $data): string
    {
        return (string) ($data['title'] ?: $data['url'] ?? '');
    }
}
