@if (! empty($b['src']))
    <figure class="bp-figure bp-card">
        @if ($block['href'])
            <a href="{{ $block['href'] }}" target="_blank" rel="noopener nofollow ugc"><img src="{{ $b['src'] }}" alt="{{ $b['alt'] }}" loading="lazy"></a>
        @else
            <img src="{{ $b['src'] }}" alt="{{ $b['alt'] }}" loading="lazy">
        @endif
        @if ($b['caption'])
            <figcaption>{{ $b['caption'] }}</figcaption>
        @endif
    </figure>
@endif
