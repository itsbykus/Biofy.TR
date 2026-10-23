@extends('layouts.app')

@section('title', __('nav.account'))

@section('content')
    <x-page-header :title="__('nav.account')" :description="__('account.help')" />

    @if ($user->deletion_requested_at)
        <div class="alert alert-warning mb-6" role="alert">
            <x-icon name="triangle-alert" class="mt-0.5" />
            <div class="flex-1">
                <p class="font-semibold">{{ __('account.deletion_scheduled', ['date' => local_time($user->deletion_requested_at->copy()->addDays($graceDays), 'd.m.Y')]) }}</p>
                <p>{{ __('account.deletion_scheduled_text') }}</p>
            </div>
            <form method="POST" action="{{ route('account.delete.cancel') }}">@csrf<button class="btn btn-dark btn-sm">{{ __('account.cancel_deletion') }}</button></form>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('account.update') }}" class="card card-pad space-y-4" novalidate>
            @csrf @method('PUT')
            <h2 class="card-title">{{ __('account.profile') }}</h2>
            <x-form.input name="name" :label="__('auth.name')" :value="$user->name" required />
            <x-form.input name="username" :label="__('auth.username')" :value="$user->username" required :help="__('account.username_help')" />
            <x-form.input name="email" type="email" :label="__('auth.email')" :value="$user->email" required :help="__('account.email_help')" />
            @if ($user->hasPassword())
                <x-form.input name="current_password" type="password" :label="__('account.current_password_for_email')" autocomplete="current-password" />
            @endif
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="locale" :label="__('account.language')" :value="$user->locale" :options="['tr' => 'Türkçe', 'en' => 'English']" />
                <x-form.select name="timezone" :label="__('account.timezone')" :value="$user->timezone" :placeholder="__('account.timezone_site_default', ['tz' => setting('site.timezone')])" :options="array_combine($timezones, $timezones)" />
            </div>
            <div class="flex justify-end"><button class="btn btn-dark">{{ __('common.save') }}</button></div>
        </form>

        <div class="space-y-6">
            <form method="POST" action="{{ route('account.password') }}" class="card card-pad space-y-4" novalidate>
                @csrf @method('PUT')
                <h2 class="card-title">{{ $user->hasPassword() ? __('account.change_password') : __('account.set_password') }}</h2>
                @if ($user->hasPassword())
                    <x-form.input name="current_password" type="password" :label="__('account.current_password')" autocomplete="current-password" required id="pw-current" />
                @else
                    <p class="muted">{{ __('account.set_password_help') }}</p>
                @endif
                <x-form.input name="password" type="password" :label="__('auth.new_password')" autocomplete="new-password" required :help="__('auth.password_rules', ['min' => max(8, (int) setting('security.password_min_length'))])" />
                <x-form.input name="password_confirmation" type="password" :label="__('auth.password_confirm')" autocomplete="new-password" required />
                <div class="flex justify-end"><button class="btn btn-dark">{{ __('account.update_password') }}</button></div>
            </form>

            <section class="card card-pad">
                <h2 class="card-title">{{ __('account.your_data') }}</h2>
                <p class="muted mt-1">{{ __('account.export_help') }}</p>
                <a href="{{ route('account.export') }}" class="btn btn-secondary mt-3"><x-icon name="download" />{{ __('account.export') }}</a>
            </section>

            @unless ($user->deletion_requested_at)
                <form method="POST" action="{{ route('account.delete') }}" class="card card-pad space-y-3 border-red-200" data-confirm="{{ __('account.delete_confirm') }}">
                    @csrf
                    <h2 class="card-title text-red-800">{{ __('account.delete') }}</h2>
                    <p class="text-sm text-zinc-600">{{ __('account.delete_help', ['days' => $graceDays]) }}</p>
                    <x-form.input name="confirm" :label="__('account.type_username', ['username' => $user->username])" autocomplete="off" />
                    <button class="btn btn-danger">{{ __('account.request_deletion') }}</button>
                </form>
            @endunless
        </div>
    </div>
@endsection
