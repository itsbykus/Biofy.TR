{{-- Click-to-load embed: nothing is requested from the provider until the visitor presses the button. --}}
<div class="bp-card">
    <div class="bp-embed {{ $variant ?? '' }}" data-embed="{{ $embed }}" data-title="{{ $title ?: $providerName }}" @if (! empty($height)) data-height="{{ (int) $height }}" @endif>
        <button type="button" class="bp-embed-load">
            @if (! empty($thumbnail))
                <img src="{{ $thumbnail }}" alt="" loading="lazy">
            @endif
            <span><x-icon :name="$icon ?? 'play'" />{{ __('profile.load_embed', ['provider' => $providerName]) }}</span>
            <small>{{ __('profile.embed_notice', ['provider' => $providerName]) }}</small>
        </button>
        <noscript><a class="bp-embed-load" href="{{ $source }}" target="_blank" rel="noopener nofollow ugc"><span>{{ __('profile.open_on', ['provider' => $providerName]) }}</span></a></noscript>
    </div>
    @if ($title)
        <div class="bp-embed-title">{{ $title }}</div>
    @endif
</div>
