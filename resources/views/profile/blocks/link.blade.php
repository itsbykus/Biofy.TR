@include('profile.blocks._button', [
    'href' => $block['href'],
    'title' => $b['title'],
    'desc' => $b['description'] ?? null,
    'thumb' => $b['thumbnail'] ?? null,
    'newTab' => $b['new_tab'] ?? true,
    'highlight' => $b['highlight'] ?? false,
    'card' => $theme['layout'] === 'grid' && ! empty($b['thumbnail']),
])
