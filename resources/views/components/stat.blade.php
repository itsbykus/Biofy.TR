@props(['label', 'value', 'hint' => null, 'icon' => null])
<div {{ $attributes->merge(['class' => 'card p-4']) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ $label }}</p>
        @if ($icon)<x-icon :name="$icon" class="size-4 text-zinc-400" />@endif
    </div>
    <p class="mt-2 text-2xl font-bold tabular-nums text-zinc-900">{{ $value }}</p>
    @if ($hint)<p class="mt-1 text-xs text-zinc-500">{{ $hint }}</p>@endif
</div>
