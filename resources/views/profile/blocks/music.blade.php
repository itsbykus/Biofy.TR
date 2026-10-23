@if (! empty($b['embed']))
    @include('profile.blocks._embed', [
        'embed' => $b['embed'],
        'title' => $b['title'] ?? '',
        'providerName' => ['spotify' => 'Spotify', 'soundcloud' => 'SoundCloud', 'apple_music' => 'Apple Music', 'deezer' => 'Deezer'][$b['provider']] ?? 'Music',
        'variant' => 'bp-embed--audio',
        'height' => $b['height'] ?? 152,
        'icon' => 'music',
        'source' => $b['url'],
    ])
@endif
