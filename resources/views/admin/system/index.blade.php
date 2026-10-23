@extends('layouts.admin')

@section('title', __('admin.nav.system'))

@section('content')
    <x-page-header :title="__('admin.nav.system')" :description="__('system.help')">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.system.cache') }}">@csrf<button class="btn btn-secondary">{{ __('system.clear_cache') }}</button></form>
            <form method="POST" action="{{ route('admin.system.maintenance') }}">@csrf<button class="btn btn-secondary">{{ __('system.run_maintenance') }}</button></form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Version & update --}}
        <section class="card card-pad space-y-3">
            <h2 class="card-title">{{ __('system.version') }}</h2>
            <dl class="grid grid-cols-2 gap-2 text-sm">
                <dt class="text-zinc-500">{{ __('system.code_version') }}</dt><dd class="font-mono">{{ $codeVersion }}</dd>
                <dt class="text-zinc-500">{{ __('system.installed_version') }}</dt><dd class="font-mono">{{ $installedVersion ?? '—' }}</dd>
                <dt class="text-zinc-500">PHP</dt><dd class="font-mono">{{ $php }}</dd>
                <dt class="text-zinc-500">Laravel</dt><dd class="font-mono">{{ $laravel }}</dd>
                <dt class="text-zinc-500">{{ __('system.database') }}</dt><dd class="font-mono">{{ $db }}</dd>
            </dl>
            @if ($pendingUpdate || $pendingMigrations)
                <div class="alert alert-warning"><x-icon name="triangle-alert" class="mt-0.5" />
                    <div class="flex-1">
                        <p class="font-semibold">{{ __('system.update_ready') }}</p>
                        @if ($pendingMigrations)<p class="mt-1 text-xs">{{ __('system.pending_migrations', ['count' => count($pendingMigrations)]) }}</p><ul class="mt-1 list-disc pl-5 font-mono text-[11px]">@foreach ($pendingMigrations as $migration)<li>{{ $migration }}</li>@endforeach</ul>@endif
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.system.update') }}" class="space-y-2" data-confirm="{{ __('system.update_confirm') }}">
                    @csrf
                    <label class="flex items-center gap-2 text-xs text-zinc-600"><input type="checkbox" name="skip_backup" value="1" class="checkbox">{{ __('system.skip_backup') }}</label>
                    <button class="btn btn-primary">{{ __('system.run_update') }}</button>
                </form>
            @else
                <p class="badge badge-green">{{ __('system.up_to_date') }}</p>
            @endif
            <p class="help">{{ __('system.update_process') }}</p>
        </section>

        {{-- Health --}}
        <section class="card card-pad">
            <h2 class="card-title mb-3">{{ __('system.health') }}</h2>
            <ul class="grid gap-2 text-sm sm:grid-cols-2">
                @foreach ($checks as $check => $result)
                    <li class="flex items-start gap-2"><x-icon :name="$result['ok'] ? 'circle-check' : 'triangle-alert'" class="mt-0.5 {{ $result['ok'] ? 'text-emerald-600' : 'text-amber-600' }}" /><span>{{ __('system.checks.'.$check) }} @isset($result['detail'])<span class="block text-xs text-zinc-500">{{ $result['detail'] }}</span>@endisset</span></li>
                @endforeach
            </ul>
        </section>

        {{-- Release integrity --}}
        <section id="release" class="card card-pad space-y-3">
            <h2 class="card-title">{{ __('system.integrity') }}</h2>
            <p class="muted">{{ __('system.integrity_help') }}</p>
            <form method="POST" action="{{ route('admin.system.verify-release') }}">@csrf<button class="btn btn-secondary">{{ __('system.verify_files') }}</button></form>
            @if ($release)
                <div @class(['alert', 'alert-success' => $release['status'] === 'ok', 'alert-warning' => in_array($release['status'], ['unsigned', 'no_manifest'], true), 'alert-error' => in_array($release['status'], ['bad_signature', 'files_mismatch'], true)])>
                    <div>
                        <p class="font-semibold">{{ __('system.release_status.'.$release['status']) }}</p>
                        <p class="text-xs">{{ __('system.release_checked', ['count' => $release['checked'], 'version' => $release['version'] ?? '?']) }}</p>
                        @foreach (['missing', 'modified'] as $kind)
                            @if ($release[$kind])<p class="mt-2 text-xs font-semibold">{{ __('system.release_'.$kind) }}</p><ul class="font-mono text-[11px]">@foreach ($release[$kind] as $file)<li>{{ $file }}</li>@endforeach</ul>@endif
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Exposure probe --}}
        <section id="probe" class="card card-pad space-y-3">
            <h2 class="card-title">{{ __('system.exposure') }}</h2>
            <p class="muted">{{ __('system.exposure_help') }}</p>
            <form method="POST" action="{{ route('admin.system.probe') }}">@csrf<button class="btn btn-secondary">{{ __('system.run_probe') }}</button></form>
            @if ($probe)
                <ul class="space-y-1 text-sm">
                    @foreach ($probe as $path => $result)
                        <li class="flex items-center gap-2"><x-icon :name="$result['exposed'] ? 'circle-x' : 'circle-check'" class="{{ $result['exposed'] ? 'text-red-600' : 'text-emerald-600' }}" /><span class="font-mono text-xs">{{ $path }}</span><span class="text-xs text-zinc-500">({{ $result['status'] }})</span></li>
                    @endforeach
                </ul>
                @if (collect($probe)->contains('exposed', true))<p class="alert alert-error"><x-icon name="triangle-alert" class="mt-0.5" /><span>{{ __('system.exposed_warning') }}</span></p>@endif
            @endif
        </section>

        {{-- Cron --}}
        <section class="card card-pad space-y-3 lg:col-span-2">
            <h2 class="card-title">{{ __('system.cron') }}</h2>
            <p class="muted">{{ __('system.cron_help') }}</p>
            <p class="text-sm">{{ __('system.cron_last', ['time' => $lastCron ? local_time($lastCron) : __('common.never')]) }}</p>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold text-zinc-500 uppercase">{{ __('system.cron_command') }}</p>
                    <p class="code mt-1 block">* * * * * {{ $cronCommand }} >> /dev/null 2>&1</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-zinc-500 uppercase">{{ __('system.cron_url') }}</p>
                    @if ($cronUrl)
                        <div class="mt-1 flex items-center gap-2"><p class="code block min-w-0 flex-1 truncate">{{ $cronUrl }}</p><button type="button" class="btn btn-ghost btn-sm" data-copy="{{ $cronUrl }}" data-copied="{{ __('common.copied') }}">{{ __('common.copy') }}</button></div>
                        <p class="help">{{ __('system.cron_url_help') }}</p>
                    @endif
                    <form method="POST" action="{{ route('admin.system.cron-token') }}" class="mt-2" data-confirm="{{ __('system.cron_regen_confirm') }}">@csrf<button class="btn btn-ghost btn-sm">{{ __('system.cron_regenerate') }}</button></form>
                </div>
            </div>
        </section>
    </div>
@endsection
