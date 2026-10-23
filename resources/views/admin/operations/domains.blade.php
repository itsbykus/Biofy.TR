@extends('layouts.admin')

@section('title', __('admin.nav.domains'))

@section('content')
    <x-page-header :title="__('admin.nav.domains')" :description="__('admin.domains.help')" />

    <form method="GET" class="mb-4 flex gap-2">
        <x-form.select name="status" :value="request('status')" :placeholder="__('common.all')" :options="['pending' => __('common.status.pending'), 'verified' => __('common.status.verified'), 'disabled' => __('common.status.disabled')]" data-autosubmit :aria-label="__('common.status_label')" />
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('domains.hostname') }}</th><th>{{ __('admin.profiles.owner') }}</th><th>{{ __('common.status_label') }}</th><th>HTTPS</th><th class="hidden lg:table-cell">{{ __('domains.last_checked_short') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($domains as $domain)
                    <tr>
                        <td><span class="font-semibold">{{ $domain->hostname }}</span><p class="text-xs text-zinc-500">{{ $domain->profile ? '/'.$domain->profile->slug : __('domains.no_page') }}</p></td>
                        <td><a href="{{ route('admin.users.show', $domain->user_id) }}" class="hover:underline">{{ '@'.$domain->user?->username }}</a></td>
                        <td><x-status-badge :status="$domain->status" />@if ($domain->disabled_reason)<p class="text-xs text-zinc-500">{{ $domain->disabled_reason }}</p>@endif</td>
                        <td><span class="badge {{ $domain->isSecure() ? 'badge-green' : 'badge-gray' }}">{{ __('domains.ssl.'.$domain->ssl_status) }}</span></td>
                        <td class="hidden text-xs text-zinc-500 lg:table-cell">{{ $domain->last_checked_at ? local_time($domain->last_checked_at) : '—' }}</td>
                        <td class="text-right">
                            <div class="flex flex-wrap justify-end gap-1">
                                <form method="POST" action="{{ route('admin.domains.recheck', $domain) }}">@csrf<button class="btn btn-ghost btn-sm">{{ __('domains.verify') }}</button></form>
                                @if ($domain->status === 'disabled')
                                    <form method="POST" action="{{ route('admin.domains.enable', $domain) }}">@csrf<button class="btn btn-secondary btn-sm">{{ __('admin.domains.enable') }}</button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.domains.disable', $domain) }}" class="flex gap-1">@csrf
                                        <input name="reason" required class="input min-h-8 w-36 py-1 text-xs" placeholder="{{ __('admin.profiles.reason') }}" aria-label="{{ __('admin.profiles.reason') }}">
                                        <button class="btn btn-danger-ghost btn-sm">{{ __('admin.domains.disable') }}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-zinc-500">{{ __('domains.empty_title') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $domains->links() }}</div>
@endsection
