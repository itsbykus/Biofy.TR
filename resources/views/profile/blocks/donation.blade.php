@php($brands = ['buymeacoffee' => 'buymeacoffee', 'patreon' => 'patreon', 'kofi' => 'kofi', 'paypal' => 'paypal', 'github_sponsors' => 'github_sponsors'])
@include('profile.blocks._button', [
    'href' => $block['href'],
    'title' => $b['title'],
    'desc' => $b['description'] ?? null,
    'brand' => $brands[$b['platform'] ?? ''] ?? null,
    'icon' => 'heart',
])
