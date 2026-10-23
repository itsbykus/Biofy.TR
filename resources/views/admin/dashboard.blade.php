@extends('layouts.admin')

@section('title', __('admin.nav.dashboard'))

@section('content')
    <x-page-header :title="__('admin.nav.dashboard')" :description="__('admin.dashboard.help')" />

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('admin.kpi.users_total')" :value="num($kpi['users_total'])" :hint="__('admin.kpi.users_new', ['week' => num($kpi['users_new_7d']), 'month' => num($kpi['users_new_30d'])])" icon="users" />
        <x-stat :label="__('admin.kpi.users_active')" :value="num($kpi['users_active'])" :hint="__('admin.kpi.users_active_hint', ['n' => num($kpi['users_active_30d']), 'suspended' => num($kpi['users_suspended'])])" icon="activity" />
        <x-stat :label="__('admin.kpi.profiles')" :value="num($kpi['profiles_published'])" :hint="__('admin.kpi.profiles_hint', ['draft' => num($kpi['profiles_draft'])])" icon="layers" />
        <x-stat :label="__('admin.kpi.links')" :value="num($kpi['links_total'])" icon="link" />
        <x-stat :label="__('admin.kpi.views_today')" :value="num($today['views'])" :hint="__('admin.kpi.month', ['n' => num($month['views'])])" icon="eye" />
        <x-stat :label="__('admin.kpi.clicks_today')" :value="num($today['clicks'])" :hint="__('admin.kpi.month', ['n' => num($month['clicks'])])" icon="mouse-pointer-click" />
        <x-stat :label="__('admin.kpi.storage')" :value="human_bytes($kpi['storage_bytes'])" :hint="$kpi['disk_free'] ? __('admin.kpi.disk_free', ['size' => human_bytes($kpi['disk_free'])]) : null" icon="hard-drive" />
        <div class="card p-4">
            <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ __('admin.kpi.health') }}</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($health as $check => $result)
                    <li class="flex items-center gap-2"><x-icon :name="$result['ok'] ? 'circle-check' : 'circle-x'" class="{{ $result['ok'] ? 'text-emerald-600' : 'text-red-600' }}" />{{ __('system.checks.'.$check) }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_320px]">
        <div class="card card-pad">
            <h2 class="card-title mb-3">{{ __('admin.dashboard.traffic') }}</h2>
            <x-line-chart :series="$series" :keys="['views', 'clicks']" />
        </div>
        <div class="card card-pad">
            <h2 class="card-title mb-4">{{ __('admin.dashboard.by_package') }}</h2>
            <x-bar-list :items="$byPackage->map(fn ($r) => ['label' => $r['name'], 'views' => $r['total']])->all()" value-key="views" />
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="card card-pad">
            <h2 class="card-title mb-4">{{ __('admin.dashboard.top_profiles') }}</h2>
            @forelse ($topProfiles as $row)
                <div class="flex items-center justify-between py-1.5 text-sm"><a href="{{ url('/'.$row['slug']) }}" target="_blank" rel="noopener" class="truncate hover:underline">/{{ $row['slug'] }}</a><span class="tabular-nums text-zinc-600">{{ num($row['views']) }}</span></div>
            @empty
                <p class="muted">{{ __('analytics.no_data') }}</p>
            @endforelse
        </div>
        <div class="card card-pad">
            <div class="mb-3 flex items-center justify-between"><h2 class="card-title">{{ __('admin.dashboard.recent_audit') }}</h2>@can('audit.view')<a href="{{ route('admin.audit.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">{{ __('common.all') }}</a>@endcan</div>
            <ul class="space-y-2 text-sm">
                @forelse ($audit as $log)
                    <li><span class="font-medium">{{ $log->actor?->name ?? __('admin.system_actor') }}</span> <span class="text-zinc-600">{{ audit_label($log->action) }}</span><br><span class="text-xs text-zinc-500">{{ local_time($log->created_at) }}</span></li>
                @empty
                    <li class="muted">{{ __('admin.dashboard.no_audit') }}</li>
                @endforelse
            </ul>
        </div>
        <div class="card card-pad">
            <h2 class="card-title mb-3">{{ __('admin.dashboard.recent_security') }}</h2>
            <ul class="space-y-2 text-sm">
                @forelse ($securityEvents as $event)
                    <li><span class="font-medium">{{ __('security.events.'.$event->type) }}</span> · <span class="text-zinc-600">{{ $event->user?->username ?? '—' }}</span><br><span class="text-xs text-zinc-500">{{ $event->ip_address }} · {{ local_time($event->created_at) }}</span></li>
                @empty
                    <li class="muted">{{ __('admin.dashboard.no_security') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
