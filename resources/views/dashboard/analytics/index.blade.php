@extends('layouts.app')

@section('title', __('nav.analytics'))

@section('content')
    <x-page-header :title="__('nav.analytics')" :description="__('analytics.help')">
        <x-slot:actions>
            @if ($canExport && $overview)
                <a href="{{ route('analytics.export', array_filter(['profile' => $selected?->id, 'from' => $start->toDateString(), 'to' => $end->toDateString()])) }}" class="btn btn-secondary"><x-icon name="download" />CSV</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <x-form.select name="profile" :label="__('profiles.page')" :value="$selected?->id" :placeholder="__('analytics.all_pages')" :options="$profiles->mapWithKeys(fn ($p) => [$p->id => $p->display_name.' (/'.$p->slug.')'])->all()" class="sm:w-64" />
        <x-form.input name="from" type="date" :label="__('analytics.from')" :value="$start->toDateString()" />
        <x-form.input name="to" type="date" :label="__('analytics.to')" :value="$end->toDateString()" />
        <button class="btn btn-dark">{{ __('common.apply') }}</button>
    </form>
    @if ($historyDays > 0)
        <p class="-mt-3 mb-4 text-xs text-zinc-500">{{ __('analytics.history_limit', ['days' => $historyDays]) }}</p>
    @endif

    @if (! $overview)
        <x-empty icon="chart-column" :title="__('analytics.empty_title')" :text="__('analytics.empty_text')" />
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat :label="__('analytics.views')" :value="num($overview['totals']['views'])" icon="eye" />
            <x-stat :label="__('analytics.uniques')" :value="num($overview['totals']['uniques'])" :hint="__('analytics.uniques_hint')" icon="users" />
            <x-stat :label="__('analytics.clicks')" :value="num($overview['totals']['clicks'])" icon="mouse-pointer-click" />
            <x-stat :label="__('analytics.ctr')" :value="'%'.num($overview['totals']['ctr'], 1)" :hint="__('analytics.ctr_hint')" icon="activity" />
        </div>

        <div class="card card-pad mt-4">
            <h2 class="card-title mb-3">{{ __('analytics.daily') }}</h2>
            <x-line-chart :series="$overview['series']" :keys="['views', 'uniques', 'clicks']" />
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="card card-pad">
                <h2 class="card-title mb-4">{{ __('analytics.top_links') }}</h2>
                <x-bar-list :items="$topBlocks" value-key="clicks" :empty="__('analytics.no_clicks')" />
            </div>

            @if ($detailed)
                <div class="card card-pad">
                    <h2 class="card-title mb-4">{{ __('analytics.referrers') }}</h2>
                    <x-bar-list :items="$breakdowns['referrer_host']" value-key="views" :empty="__('analytics.no_referrers')" />
                </div>
                <div class="card card-pad">
                    <h2 class="card-title mb-4">{{ __('analytics.devices') }}</h2>
                    <x-bar-list :items="$breakdowns['device']" value-key="views" translate="analytics.device_names" />
                </div>
                <div class="card card-pad">
                    <h2 class="card-title mb-4">{{ __('analytics.browsers') }}</h2>
                    <x-bar-list :items="$breakdowns['browser']" value-key="views" translate="analytics.browser_names" />
                </div>
                <div class="card card-pad">
                    <h2 class="card-title mb-4">{{ __('analytics.countries') }}</h2>
                    <x-bar-list :items="$breakdowns['country']" value-key="views" :empty="__('analytics.no_country')" />
                </div>
                <div class="card card-pad">
                    <h2 class="card-title mb-4">{{ __('analytics.trends') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach (['weekly' => $weekly, 'monthly' => $monthly] as $period => $rows)
                            <div>
                                <p class="mb-2 text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ __('analytics.'.$period) }}</p>
                                <table class="table text-xs">
                                    <thead><tr><th>{{ __('analytics.period') }}</th><th class="text-right">{{ __('analytics.views') }}</th><th class="text-right">{{ __('analytics.clicks') }}</th></tr></thead>
                                    <tbody>
                                        @foreach (array_slice(array_reverse($rows), 0, 8) as $row)
                                            <tr><td>{{ $row['period'] }}</td><td class="text-right tabular-nums">{{ num($row['views']) }}</td><td class="text-right tabular-nums">{{ num($row['clicks']) }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-6">
                    <p class="font-semibold">{{ __('analytics.detailed_locked_title') }}</p>
                    <p class="mt-1 text-sm text-zinc-600">{{ __('analytics.detailed_locked_text') }}</p>
                    <a href="{{ route('plan.show') }}" class="btn btn-primary btn-sm mt-4">{{ __('plans.see_plans') }}</a>
                </div>
            @endif
        </div>
        <p class="mt-4 text-xs text-zinc-500">{{ __('analytics.privacy_note') }}</p>
    @endif
@endsection
