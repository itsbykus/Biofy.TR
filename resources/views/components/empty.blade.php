@props(['icon' => 'inbox', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-full bg-brand-soft text-brand-ink"><x-icon :name="$icon" class="size-5" /></span>
    <h3 class="mt-4 text-base font-semibold text-zinc-900">{{ $title }}</h3>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-zinc-500">{{ $text }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
