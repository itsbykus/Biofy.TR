@if (! empty($b['embed']))
    @include('profile.blocks._embed', [
        'embed' => $b['embed'],
        'title' => '',
        'providerName' => 'OpenStreetMap',
        'variant' => 'bp-embed--map',
        'icon' => 'map-pin',
        'source' => $block['href'],
    ])
@endif
@include('profile.blocks._button', ['href' => $block['href'], 'title' => $b['label'] ?: __('profile.directions'), 'desc' => $b['address'], 'icon' => 'map-pin'])
