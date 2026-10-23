@props(['series', 'keys' => ['views', 'clicks']])
@php
    $points = collect($series)->map(fn ($d) => ['label' => \Illuminate\Support\Carbon::parse($d['date'])->translatedFormat('d M'), 'values' => $d])->values()->all();
    $chart = \App\Support\SvgChart::lines($points, $keys);
@endphp
<figure {{ $attributes->merge(['class' => 'w-full']) }}>
    <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" class="h-auto w-full" role="img" aria-label="{{ __('analytics.chart_title') }}">
        @foreach ($chart['ticks'] as $tick)
            <line x1="{{ $chart['left'] }}" x2="{{ $chart['width'] - 8 }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" class="chart-grid" stroke-width="1" />
            <text x="{{ $chart['left'] - 6 }}" y="{{ $tick['y'] + 3 }}" text-anchor="end" class="chart-axis">{{ num($tick['value']) }}</text>
        @endforeach
        @if (in_array('views', $keys, true))
            <path d="{{ $chart['areas']['views'] }}" class="chart-area-views" />
        @endif
        @foreach ($keys as $key)
            <path d="{{ $chart['paths'][$key] }}" class="chart-line-{{ $key }}" stroke-linejoin="round" stroke-linecap="round" />
        @endforeach
        @foreach ($chart['labels'] as $label)
            <text x="{{ $label['x'] }}" y="{{ $chart['height'] - 6 }}" text-anchor="middle" class="chart-axis">{{ $label['text'] }}</text>
        @endforeach
    </svg>
    <figcaption class="mt-2 flex flex-wrap gap-4 text-xs text-zinc-600">
        @foreach ($keys as $key)
            <span class="inline-flex items-center gap-1.5"><svg class="h-2 w-4" viewBox="0 0 16 8" aria-hidden="true"><line x1="0" x2="16" y1="4" y2="4" class="chart-line-{{ $key }}" /></svg>{{ __('analytics.'.$key) }}</span>
        @endforeach
    </figcaption>
    {{-- Accessible data table alternative to the chart --}}
    <details class="mt-2 text-xs text-zinc-500">
        <summary class="cursor-pointer">{{ __('analytics.show_table') }}</summary>
        <div class="mt-2 max-h-64 overflow-auto">
            <table class="table">
                <thead><tr><th>{{ __('analytics.date') }}</th>@foreach ($keys as $key)<th class="text-right">{{ __('analytics.'.$key) }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($series as $row)
                        <tr><td>{{ $row['date'] }}</td>@foreach ($keys as $key)<td class="text-right tabular-nums">{{ num($row[$key]) }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
