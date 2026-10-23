@extends('layouts.admin')

@section('title', __('admin.nav.security_events'))

@section('content')
    <x-page-header :title="__('admin.nav.security_events')" :description="__('admin.security_events.help')" />

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <x-form.input name="user" :label="__('auth.username')" :value="request('user')" />
        <x-form.select name="type" :label="__('security.event')" :value="request('type')" :placeholder="__('common.all')" :options="collect(trans('security.events'))->all()" />
        <button class="btn btn-dark">{{ __('common.filter') }}</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('common.date') }}</th><th>{{ __('security.event') }}</th><th>{{ __('auth.username') }}</th><th>IP</th><th class="hidden lg:table-cell">{{ __('security.device') }}</th></tr></thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td class="text-xs whitespace-nowrap text-zinc-500">{{ local_time($event->created_at) }}</td>
                        <td @class(['font-medium', 'text-red-700' => in_array($event->type, ['login_failed', 'two_factor_failed', 'lockout'], true)])>{{ __('security.events.'.$event->type) }}</td>
                        <td>@if ($event->user)<a href="{{ route('admin.users.show', $event->user_id) }}" class="hover:underline">{{ '@'.$event->user->username }}</a>@else — @endif</td>
                        <td class="font-mono text-xs">{{ $event->ip_address }}</td>
                        <td class="hidden text-xs text-zinc-500 lg:table-cell">{{ \App\Support\UserAgent::describe($event->user_agent) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-zinc-500">{{ __('security.no_events') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
@endsection
