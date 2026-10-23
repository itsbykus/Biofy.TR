@props(['href' => null, 'size' => 'md', 'variant' => 'header'])
{{--
    header: site logo (image, or the "b" mark + site name), height = Admin → Brand → logo height (--logo-h).
    footer: footer logo (falls back to the site logo) + optional brand text, height --footer-logo-h.
    size="sm": fixed compact logo for the dashboard sidebar.
--}}
@php
    $footer = $variant === 'footer';
    $src = $footer ? ($site['footer']['logo'] ?? null) : ($site['logo'] ?? null);
    $text = $footer ? ($site['footer']['brand_text'] ?? $site['name']) : ($site['name'] ?? 'Biofy.TR');
    $showText = $footer ? ($site['footer']['show_brand_text'] ?? true) : ! $src;
    $height = $size === 'sm' ? 'h-7' : ($footer ? 'h-(--footer-logo-h)' : 'h-(--logo-h)');
    $mark = $size === 'sm' ? 'size-7 text-sm' : ($footer ? 'size-(--footer-logo-h) text-[calc(var(--footer-logo-h)*0.55)]' : 'size-(--logo-h) text-[calc(var(--logo-h)*0.55)]');
    $label = $size === 'sm' ? 'text-base' : ($footer ? 'text-[calc(var(--footer-logo-h)*0.58)]' : 'text-[calc(var(--logo-h)*0.58)]');
@endphp
<a href="{{ $href ?? route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 font-bold tracking-tight']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $showText ? '' : $text }}" class="{{ $height }} w-auto max-w-[14rem] object-contain">
    @else
        <span class="grid {{ $mark }} shrink-0 place-items-center rounded-[28%] bg-brand font-black text-ink" aria-hidden="true">b</span>
    @endif
    @if ($showText)
        <span class="{{ $label }} leading-none">{{ $text }}</span>
    @endif
</a>
