<details class="bp-card bp-group" @if (! ($b['collapsed'] ?? false)) open @endif>
    <summary>{{ $b['title'] }}</summary>
    <div class="bp-group-items">
        @foreach ($b['items'] as $i => $item)
            @include('profile.blocks._button', ['href' => $block['item_hrefs'][$i] ?? '#', 'title' => $item['title']])
        @endforeach
    </div>
</details>
