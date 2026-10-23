{{-- Miniature of a theme drawn with SVG (fills are attributes, so no inline CSS is needed under the CSP). --}}
@php
    $btnRadius = min(12, (int) $t['button_radius'] / 3);
    $isOutline = in_array($t['button_style'], ['outline', 'underline', 'glass'], true);
    $gradId = 'g-'.$key.'-'.substr(md5(uniqid('', true)), 0, 6);
@endphp
<svg viewBox="0 0 120 150" class="block h-auto w-full" aria-hidden="true">
    @if ($t['bg_type'] === 'gradient')
        <defs><linearGradient id="{{ $gradId }}" x1="0" y1="0" x2="0.6" y2="1"><stop offset="0" stop-color="{{ $t['bg_gradient_from'] }}"/><stop offset="1" stop-color="{{ $t['bg_gradient_to'] }}"/></linearGradient></defs>
        <rect width="120" height="150" fill="url(#{{ $gradId }})"/>
    @else
        <rect width="120" height="150" fill="{{ $t['bg_color'] }}"/>
    @endif
    @if ($t['banner_style'] === 'cover')
        <rect width="120" height="34" fill="{{ $t['card_bg'] }}" opacity="0.9"/>
    @endif
    @php($ax = $t['align'] === 'left' ? 22 : 60)
    @if ($t['avatar_shape'] === 'circle')
        <circle cx="{{ $ax }}" cy="34" r="12" fill="{{ $t['button_bg'] }}" stroke="{{ $t['bg_color'] }}" stroke-width="2"/>
    @else
        <rect x="{{ $ax - 12 }}" y="22" width="24" height="24" rx="{{ $t['avatar_shape'] === 'rounded' ? 6 : 2 }}" fill="{{ $t['button_bg'] }}" stroke="{{ $t['bg_color'] }}" stroke-width="2"/>
    @endif
    <rect x="{{ $t['align'] === 'left' ? 10 : 36 }}" y="52" width="48" height="5" rx="2.5" fill="{{ $t['text_color'] }}"/>
    <rect x="{{ $t['align'] === 'left' ? 10 : 44 }}" y="61" width="32" height="3" rx="1.5" fill="{{ $t['muted_color'] }}"/>
    @for ($i = 0; $i < 4; $i++)
        @php($y = 74 + $i * 17)
        @if ($t['button_style'] === 'underline')
            <rect x="12" y="{{ $y + 12 }}" width="96" height="1.2" fill="{{ $t['button_border_color'] }}"/>
            <rect x="14" y="{{ $y + 5 }}" width="40" height="3" rx="1.5" fill="{{ $t['button_text'] }}"/>
        @else
            <rect x="12" y="{{ $y }}" width="96" height="13" rx="{{ $btnRadius }}" fill="{{ $isOutline ? 'none' : $t['button_bg'] }}" stroke="{{ $t['button_border_color'] }}" stroke-width="{{ $isOutline || $t['button_border_width'] ? 1 : 0 }}" @if ($t['button_style'] === 'glass') fill-opacity="0.15" @endif/>
            @if ($t['button_shadow'] === 'hard')<rect x="14" y="{{ $y + 2 }}" width="96" height="13" rx="{{ $btnRadius }}" fill="{{ $t['button_border_color'] }}" opacity="0.9" transform="translate(0 0)" />
                <rect x="12" y="{{ $y }}" width="96" height="13" rx="{{ $btnRadius }}" fill="{{ $t['button_bg'] }}" stroke="{{ $t['button_border_color'] }}" stroke-width="1"/>@endif
            <rect x="44" y="{{ $y + 5 }}" width="32" height="3" rx="1.5" fill="{{ $t['button_text'] }}"/>
        @endif
    @endfor
</svg>
