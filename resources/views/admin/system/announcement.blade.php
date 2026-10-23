@extends('layouts.admin')

@section('title', __('admin.nav.announcement'))

@section('content')
    <x-page-header :title="__('admin.nav.announcement')" :description="__('admin.announcement.help')" />

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card card-pad space-y-3">
            <h2 class="card-title">{{ __('admin.announcement.banner_title') }}</h2>
            <p class="muted">{{ __('admin.announcement.banner_help') }}</p>
            <a href="{{ route('admin.settings.edit', 'announcement') }}" class="btn btn-secondary">{{ __('admin.announcement.edit_banner') }}</a>
        </section>

        <section class="card card-pad space-y-4">
            <h2 class="card-title">{{ __('admin.announcement.email_title') }}</h2>
            @if ($state)
                <div class="alert alert-info"><x-icon name="send" class="mt-0.5" /><p>{{ __('admin.announcement.progress', ['sent' => $state['sent'], 'remaining' => $state['remaining'] ?? '?']) }}</p></div>
                <form method="POST" action="{{ route('admin.announcement.send') }}">@csrf<button class="btn btn-dark">{{ __('admin.announcement.continue') }}</button></form>
            @else
                <form method="POST" action="{{ route('admin.announcement.send') }}" class="space-y-3" data-confirm="{{ __('admin.announcement.confirm') }}">
                    @csrf
                    <x-form.textarea name="message" :label="__('admin.announcement.message')" rows="6" maxlength="3000" required :help="__('admin.announcement.message_help')" />
                    <x-form.select name="audience" :label="__('admin.announcement.audience')" :options="['verified' => __('admin.announcement.audience_verified'), 'all' => __('admin.announcement.audience_all')]" />
                    <button class="btn btn-dark">{{ __('admin.announcement.start') }}</button>
                    <p class="help">{{ __('admin.announcement.batch_help') }}</p>
                </form>
            @endif
        </section>
    </div>
@endsection
