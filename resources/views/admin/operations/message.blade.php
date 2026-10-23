@extends('layouts.admin')

@section('title', $message->subject)

@section('content')
    <a href="{{ route('admin.messages.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">← {{ __('admin.nav.messages') }}</a>
    <x-page-header class="mt-1" :title="$message->subject">
        <x-slot:actions>
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}" class="btn btn-dark"><x-icon name="send" />{{ __('admin.messages.reply') }}</a>
            <form method="POST" action="{{ route('admin.messages.archive', $message) }}">@csrf<button class="btn btn-secondary">{{ $message->status === 'archived' ? __('admin.messages.unarchive') : __('admin.messages.archive') }}</button></form>
            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="{{ __('admin.messages.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost">{{ __('common.delete') }}</button></form>
        </x-slot:actions>
    </x-page-header>

    <div class="card card-pad">
        <dl class="grid gap-3 text-sm sm:grid-cols-4">
            <div><dt class="text-zinc-500">{{ __('contact.name') }}</dt><dd class="font-medium">{{ $message->name }}</dd></div>
            <div><dt class="text-zinc-500">{{ __('contact.email') }}</dt><dd class="font-medium">{{ $message->email }}</dd></div>
            <div><dt class="text-zinc-500">{{ __('contact.package') }}</dt><dd class="font-medium">{{ $message->package?->translate('name') ?? '—' }}</dd></div>
            <div><dt class="text-zinc-500">{{ __('common.date') }}</dt><dd class="font-medium">{{ local_time($message->created_at) }}</dd></div>
        </dl>
        @if ($message->user)
            <p class="mt-3 text-sm">{{ __('admin.messages.from_user') }} <a href="{{ route('admin.users.show', $message->user) }}" class="font-medium underline">{{ '@'.$message->user->username }}</a></p>
        @endif
        <div class="mt-6 rounded-lg bg-zinc-50 p-4 text-sm whitespace-pre-line">{{ $message->message }}</div>
    </div>
@endsection
