{{-- Shared link button. $href is either a tracked /go/... path or (preview) a validated destination. --}}
@php
    $newTab = $newTab ?? true;
    $external = $href && ! str_starts_with($href, 'mailto:') && ! str_starts_with($href, 'tel:');
@endphp
<a class="bp-btn @if ($highlight ?? false) bp-btn--hl @endif @if ($card ?? false) bp-btn--card @endif" href="{{ $href ?? '#' }}"
   @if ($external && $newTab) target="_blank" @endif rel="noopener nofollow ugc">
    @if (! empty($thumb))
        <img class="bp-btn-thumb" src="{{ $thumb }}" alt="" loading="lazy">
    @elseif (! empty($brand))
        <x-brand-icon :platform="$brand" class="bp-btn-icon" label="" />
    @elseif (! empty($icon))
        <x-icon :name="$icon" class="bp-btn-icon" />
    @endif
    <span class="bp-btn-text">
        <span class="bp-btn-title">{{ $title }}</span>
        @if (! empty($desc))
            <span class="bp-btn-desc">{{ $desc }}</span>
        @endif
    </span>
</a>
