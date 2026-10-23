@include('profile.blocks._embed', [
    'embed' => $b['embed'],
    'title' => $b['title'] ?? '',
    'providerName' => $b['provider'] === 'vimeo' ? 'Vimeo' : 'YouTube',
    'thumbnail' => $b['thumbnail'] ?? null,
    'source' => $b['url'],
])
