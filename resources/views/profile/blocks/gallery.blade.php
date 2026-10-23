@if (! empty($b['items']))
    <div>
        @if ($b['title'])
            <h2 class="bp-section-title">{{ $b['title'] }}</h2>
        @endif
        <div class="bp-gallery @if ((int) ($b['columns'] ?? 2) === 3) bp-gallery--3 @endif">
            @foreach ($b['items'] as $item)
                <img src="{{ $item['src'] }}" alt="{{ $item['alt'] }}" loading="lazy">
            @endforeach
        </div>
    </div>
@endif
