@extends('layouts.admin')

@section('title', __('admin.nav.backups'))

@section('content')
    <x-page-header :title="__('admin.nav.backups')" :description="__('backups.help')">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-dark"><x-icon name="database" />{{ __('backups.create') }}</button></form>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-3 md:grid-cols-3">
        <x-stat :label="__('backups.last')" :value="$lastBackup ? local_time($lastBackup) : __('common.never')" icon="history" />
        <x-stat :label="__('backups.encryption')" :value="$encrypt ? __('common.enabled') : __('common.disabled')" :hint="__('backups.encryption_hint')" icon="lock" />
        <x-stat :label="__('backups.schedule')" :value="__('backups.schedules.'.$schedule)" :hint="__('backups.schedule_hint')" icon="clock" />
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('backups.file') }}</th><th>{{ __('common.status_label') }}</th><th class="hidden md:table-cell">{{ __('backups.size') }}</th><th class="hidden lg:table-cell">{{ __('backups.contents') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>
                            <p class="font-mono text-xs font-medium">{{ $record->filename }}</p>
                            <p class="text-xs text-zinc-500">{{ __('backups.types.'.$record->type) }} · {{ local_time($record->created_at) }} @if ($record->creator) · {{ $record->creator->name }}@endif</p>
                            @if ($record->error)<p class="text-xs text-red-700">{{ \Illuminate\Support\Str::limit($record->error, 160) }}</p>@endif
                        </td>
                        <td><x-status-badge :status="$record->status" />@if ($record->encrypted)<span class="badge badge-blue"><x-icon name="lock" class="size-3" /></span>@endif</td>
                        <td class="hidden tabular-nums md:table-cell">{{ human_bytes($record->size) }}</td>
                        <td class="hidden text-xs text-zinc-500 lg:table-cell">
                            @if ($record->manifest)
                                v{{ $record->manifest['app_version'] ?? '?' }} · {{ num(array_sum($record->manifest['tables'] ?? [])) }} {{ __('backups.rows') }} · {{ num($record->manifest['media_count'] ?? 0) }} {{ __('backups.files') }}
                            @endif
                        </td>
                        <td>
                            @if ($record->status === 'completed')
                                <div class="flex flex-wrap justify-end gap-1">
                                    <form method="POST" action="{{ route('admin.backups.verify', $record) }}">@csrf<button class="btn btn-ghost btn-sm">{{ __('backups.verify') }}</button></form>
                                    <a href="{{ route('admin.backups.download', $record) }}" class="btn btn-ghost btn-sm"><x-icon name="download" class="size-3.5" /></a>
                                    <details class="relative">
                                        <summary class="btn btn-secondary btn-sm list-none">{{ __('backups.restore') }}</summary>
                                        <form method="POST" action="{{ route('admin.backups.restore', $record) }}" class="absolute right-0 z-10 mt-1 w-80 space-y-2 rounded-xl border border-red-200 bg-white p-4 shadow-xl" data-confirm="{{ __('backups.restore_confirm') }}">
                                            @csrf
                                            <p class="text-xs text-red-800">{{ __('backups.restore_warning') }}</p>
                                            <x-form.input name="confirm" :label="__('backups.type_restore')" autocomplete="off" :id="'restore-'.$record->id" />
                                            <button class="btn btn-danger w-full">{{ __('backups.restore_now') }}</button>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('admin.backups.destroy', $record) }}" data-confirm="{{ __('backups.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm btn-icon" aria-label="{{ __('common.delete') }}"><x-icon name="trash-2" class="size-3.5" /></button></form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-zinc-500">{{ __('backups.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data" class="card card-pad space-y-3">
            @csrf
            <h2 class="card-title">{{ __('backups.upload') }}</h2>
            <p class="muted">{{ __('backups.upload_help', ['max' => $maxUpload]) }}</p>
            <input type="file" name="file" accept=".zip,.biofy-backup" required class="text-sm" aria-label="{{ __('backups.file') }}">
            <button class="btn btn-secondary">{{ __('backups.upload_button') }}</button>
        </form>
        <div class="card card-pad text-sm text-zinc-600">
            <h2 class="card-title mb-2">{{ __('backups.notes_title') }}</h2>
            <ul class="list-disc space-y-1 pl-5">
                <li>{{ __('backups.note_location') }} <span class="code">{{ str_replace(base_path(), '', $directory) }}</span></li>
                <li>{{ __('backups.note_key') }}</li>
                <li>{{ __('backups.note_restore') }}</li>
                <li>{{ __('backups.note_env') }}</li>
            </ul>
        </div>
    </div>
@endsection
