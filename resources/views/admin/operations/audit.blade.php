@extends('layouts.admin')

@section('title', __('admin.nav.audit'))

@section('content')
    <x-page-header :title="__('admin.nav.audit')" :description="__('admin.audit.help')" />

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <x-form.input name="action" :label="__('admin.audit.action')" :value="request('action')" placeholder="user. / package. / settings." />
        <x-form.select name="subject" :label="__('admin.audit.subject')" :value="request('subject')" :placeholder="__('common.all')" :options="['User' => 'User', 'Profile' => 'Profile', 'Package' => 'Package', 'FeatureDefinition' => 'Feature', 'CustomDomain' => 'Domain', 'BackupRecord' => 'Backup', 'Page' => 'Page', 'MediaFile' => 'Media']" />
        <button class="btn btn-dark">{{ __('common.filter') }}</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('common.date') }}</th><th>{{ __('admin.audit.actor') }}</th><th>{{ __('admin.audit.action') }}</th><th>{{ __('admin.audit.subject') }}</th><th class="hidden xl:table-cell">{{ __('admin.audit.details') }}</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="text-xs whitespace-nowrap text-zinc-500">{{ local_time($log->created_at) }}<br>{{ $log->ip_address }}</td>
                        <td>{{ $log->actor?->name ?? __('admin.system_actor') }}</td>
                        <td><span class="font-medium">{{ audit_label($log->action) }}</span><br><span class="font-mono text-[11px] text-zinc-500">{{ $log->action }}</span></td>
                        <td class="text-xs">{{ $log->subject_type }} @if ($log->subject_id)#{{ $log->subject_id }}@endif</td>
                        <td class="hidden max-w-md xl:table-cell"><code class="block max-h-24 overflow-auto font-mono text-[11px] break-all text-zinc-600">{{ $log->properties ? json_encode($log->properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</code></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-zinc-500">{{ __('admin.dashboard.no_audit') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
