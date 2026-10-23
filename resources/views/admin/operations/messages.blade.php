@extends('layouts.admin')

@section('title', __('admin.nav.messages'))

@section('content')
    <x-page-header :title="__('admin.nav.messages')" :description="__('admin.messages.help')">
        <x-slot:actions>
            <a href="{{ route('admin.messages.index') }}" class="btn btn-ghost btn-sm" @if (request('status') !== 'archived') aria-current="page" @endif>{{ __('admin.messages.inbox') }}</a>
            <a href="{{ route('admin.messages.index', ['status' => 'archived']) }}" class="btn btn-ghost btn-sm">{{ __('common.status.archived') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card divide-y divide-zinc-100">
        @forelse ($messages as $message)
            <a href="{{ route('admin.messages.show', $message) }}" class="flex flex-col gap-1 px-5 py-3 hover:bg-zinc-50 sm:flex-row sm:items-center sm:gap-4">
                <span class="w-20 shrink-0">@if ($message->status === 'new')<span class="badge badge-yellow">{{ __('common.status.new') }}</span>@endif</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium">{{ $message->subject }}</span>
                    <span class="block truncate text-xs text-zinc-500">{{ $message->name }} &lt;{{ $message->email }}&gt; @if ($message->package) · {{ $message->package->translate('name') }}@endif</span>
                </span>
                <span class="text-xs text-zinc-500">{{ local_time($message->created_at) }}</span>
            </a>
        @empty
            <p class="muted p-6">{{ __('admin.messages.none') }}</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $messages->links() }}</div>
@endsection
