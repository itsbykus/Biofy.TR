@include('profile.blocks._button', [
    'href' => $block['href'],
    'title' => $b['title'],
    'desc' => $b['description'] ?? null,
    'thumb' => $b['thumbnail'] ?? null,
    'brand' => $b['icon_view']['brand'] ?? null,
    'icon' => $b['icon_view']['icon'] ?? null,
    'bg' => $b['background'] ?? null,
    'newTab' => $b['new_tab'] ?? true,
    'highlight' => $b['highlight'] ?? false,
    'card' => $theme['layout'] === 'grid' && ! empty($b['thumbnail']),
])
