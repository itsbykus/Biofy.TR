<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\Embeds;
use Illuminate\Validation\ValidationException;

/** YouTube or Vimeo video, embedded from an allow-listed player host only. */
class VideoBlock extends BlockType
{
    public function key(): string
    {
        return 'video';
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
        return 'film';
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

        if ($id = Embeds::youtubeId($url)) {
            return ['url' => $url, 'provider' => 'youtube', 'video_id' => $id, 'title' => self::cleanText($data['title'] ?? '', 100)];
        }

        if ($id = Embeds::vimeoId($url)) {
            return ['url' => $url, 'provider' => 'vimeo', 'video_id' => $id, 'title' => self::cleanText($data['title'] ?? '', 100)];
        }

        throw ValidationException::withMessages(['data.url' => __('validation.custom.embed.unsupported_video')]);
    }

    public function present(array $data, array $media): array
    {
        $data['embed'] = $data['provider'] === 'youtube'
            ? Embeds::youtubeEmbed($data['video_id'])
            : Embeds::vimeoEmbed($data['video_id']);
        $data['thumbnail'] = $data['provider'] === 'youtube' ? 'https://i.ytimg.com/vi/'.$data['video_id'].'/hqdefault.jpg' : null;

        return $data;
    }

    public function summary(array $data): string
    {
        return (string) ($data['title'] ?: $data['url'] ?? '');
    }
}
