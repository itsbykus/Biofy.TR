@props(['href' => null, 'size' => 'md'])
<a href="{{ $href ?? route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-bold tracking-tight text-zinc-900']) }}>
    @if ($site['logo'] ?? null)
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="{{ $size === 'sm' ? 'h-7' : 'h-8' }} w-auto">
    @else
        <span class="grid {{ $size === 'sm' ? 'size-7 text-sm' : 'size-8' }} place-items-center rounded-lg bg-brand font-black text-ink" aria-hidden="true">b</span>
        <span class="{{ $size === 'sm' ? 'text-base' : 'text-lg' }}">{{ $site['name'] ?? 'Biofy.TR' }}</span>
    @endif
</a>
