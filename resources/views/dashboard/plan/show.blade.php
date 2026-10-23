@extends('layouts.app')

@section('title', __('nav.plan'))

@section('content')
    <x-page-header :title="__('nav.plan')" :description="__('plans.help')">
        <x-slot:actions>
            <a href="{{ route('pricing') }}" class="btn btn-secondary">{{ __('plans.compare') }}</a>
            <a href="{{ route('contact', ['package' => 'pro']) }}" class="btn btn-primary">{{ __('plans.request_change') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[340px_1fr]">
        <section class="card card-pad h-fit">
            <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ __('plans.current') }}</p>
            <p class="mt-1 text-2xl font-bold">{{ $package?->translate('name') ?? '—' }}</p>
            @if ($package?->translate('description'))<p class="mt-2 text-sm text-zinc-600">{{ $package->translate('description') }}</p>@endif
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('plans.assigned_by') }}</dt><dd>{{ $assignment ? __('plans.source.'.$assignment->source) : __('plans.source.default') }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('plans.starts') }}</dt><dd>{{ $assignment ? local_time($assignment->starts_at, 'd.m.Y') : '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('plans.ends') }}</dt><dd>{{ $assignment?->ends_at ? local_time($assignment->ends_at, 'd.m.Y') : __('plans.no_end') }}</dd></div>
            </dl>
            <p class="mt-4 rounded-lg bg-zinc-50 p-3 text-xs text-zinc-600">{{ __('plans.no_billing') }}</p>
        </section>

        <section class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>{{ __('plans.feature') }}</th><th class="text-right">{{ __('plans.your_limit') }}</th><th class="text-right">{{ __('plans.usage') }}</th></tr></thead>
                <tbody>
                    @foreach ($features as $key => $entry)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $entry['definition']->translate('name') }}</p>
                                <p class="text-xs text-zinc-500">{{ $entry['definition']->translate('description') }}</p>
                                @if ($entry['source'] === 'override')<span class="badge badge-blue mt-1">{{ __('plans.custom_for_you') }}</span>@endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if (is_bool($entry['value']))
                                    <x-icon :name="$entry['value'] ? 'circle-check' : 'circle-x'" class="inline size-5 {{ $entry['value'] ? 'text-emerald-600' : 'text-zinc-300' }}" :label="$entry['value'] ? __('common.yes') : __('common.no')" />
                                @else
                                    <span class="font-semibold tabular-nums">{{ limit_label($entry['value']) }}</span>
                                @endif
                            </td>
                            <td class="text-right text-zinc-500 tabular-nums">{{ isset($usage[$key]) ? num($usage[$key], $key === 'storage.quota_mb' ? 1 : 0) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>

    @if ($history->count() > 1)
        <section class="card card-pad mt-6">
            <h2 class="card-title mb-3">{{ __('plans.history') }}</h2>
            <ul class="divide-y divide-zinc-100 text-sm">
                @foreach ($history as $item)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span class="font-medium">{{ $item->package?->translate('name') }}</span>
                        <span class="text-zinc-500">{{ local_time($item->starts_at, 'd.m.Y') }} → {{ $item->revoked_at ? __('plans.revoked') : ($item->ends_at ? local_time($item->ends_at, 'd.m.Y') : __('plans.no_end')) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
