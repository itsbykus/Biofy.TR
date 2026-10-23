@props(['items', 'valueKey' => 'views', 'translate' => null, 'empty' => null])
@php($max = max(1, (int) (collect($items)->max($valueKey) ?? 1)))
@if (empty($items))
    <p class="text-sm text-zinc-500">{{ $empty ?? __('analytics.no_data') }}</p>
@else
    <ul class="space-y-2.5">
        @foreach ($items as $item)
            @php($label = $item['label'] ?? null)
            <li>
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="truncate text-zinc-700">{{ $label === null || $label === '' ? __('analytics.unknown') : ($translate ? __($translate.'.'.$label) : $label) }}</span>
                    <span class="font-medium tabular-nums text-zinc-900">{{ num($item[$valueKey]) }}</span>
                </div>
                <svg class="meter mt-1" viewBox="0 0 100 2" preserveAspectRatio="none" aria-hidden="true"><rect x="0" y="0" height="2" width="{{ round($item[$valueKey] / $max * 100, 2) }}" /></svg>
            </li>
        @endforeach
    </ul>
@endif
