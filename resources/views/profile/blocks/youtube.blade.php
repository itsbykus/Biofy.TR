@include('profile.blocks._embed', [
    'embed' => $b['embed'],
    'title' => $b['title'] ?? '',
    'providerName' => 'YouTube',
    'thumbnail' => $b['thumbnail'] ?? null,
    'source' => $b['url'],
])
