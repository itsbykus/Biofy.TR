<div>
    @if ($b['title'])
        <h2 class="bp-section-title">{{ $b['title'] }}</h2>
    @endif
    <div class="bp-products bp-products--{{ $b['layout'] ?? 'list' }}">
        @foreach ($b['items'] as $i => $item)
            @php($href = $block['item_hrefs'][$i] ?? null)
            <{{ $href ? 'a' : 'div' }} class="bp-card bp-product" @if ($href) href="{{ $href }}" target="_blank" rel="noopener nofollow ugc" @endif>
                @if (! empty($item['image']))
                    <img src="{{ $item['image'] }}" alt="" loading="lazy">
                @endif
                <div class="bp-product-body">
                    <div class="bp-product-title">{{ $item['title'] }}</div>
                    @if ($item['description'])
                        <p class="bp-product-desc">{{ $item['description'] }}</p>
                    @endif
                    @if ($item['price'])
                        <span class="bp-product-price">{{ $item['price'] }}</span>
                    @endif
                </div>
            </{{ $href ? 'a' : 'div' }}>
        @endforeach
    </div>
</div>
