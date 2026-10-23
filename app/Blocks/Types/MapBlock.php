<?php

namespace App\Blocks\Types;

use App\Blocks\BlockType;
use App\Models\User;
use App\Support\Embeds;

/** Location: a maps link built from the address, plus an optional OpenStreetMap embed when coordinates are given. */
class MapBlock extends BlockType
{
    public function key(): string
    {
        return 'map';
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
        return 'map-pin';
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:250'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lon'],
            'lon' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'show_map' => ['nullable', 'boolean'],
        ];
    }

    public function normalize(array $data, User $owner): array
    {
        $hasCoords = isset($data['lat'], $data['lon']) && $data['lat'] !== '' && $data['lon'] !== '';

        return [
            'label' => self::cleanText($data['label'] ?? '', 100),
            'address' => self::cleanText($data['address'] ?? '', 250),
            'lat' => $hasCoords ? round((float) $data['lat'], 6) : null,
            'lon' => $hasCoords ? round((float) $data['lon'], 6) : null,
            'show_map' => $hasCoords && (bool) ($data['show_map'] ?? false),
        ];
    }

    public function present(array $data, array $media): array
    {
        $data['embed'] = $data['show_map'] ? Embeds::openStreetMap($data['lat'], $data['lon']) : null;

        return $data;
    }

    public function clickTarget(array $presented, ?int $item = null): ?string
    {
        $query = $presented['lat'] !== null ? $presented['lat'].','.$presented['lon'] : $presented['address'];

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode((string) $query);
    }

    public function summary(array $data): string
    {
        return (string) ($data['label'] ?: $data['address'] ?? '');
    }
}
