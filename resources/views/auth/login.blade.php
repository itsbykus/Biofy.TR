@extends('layouts.auth')

@section('title', __('auth.sign_in'))
@section('heading', setting_text('auth.login_title'))
@section('subheading', setting_text('auth.login_subtitle'))

@section('content')
    @if ($googleEnabled)
        @include('auth.partials.google-button', ['label' => __('auth.continue_with_google')])
        <div class="my-5 flex items-center gap-3 text-xs text-zinc-400"><span class="h-px flex-1 bg-zinc-200"></span>{{ __('auth.or') }}<span class="h-px flex-1 bg-zinc-200"></span></div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4" novalidate>
        @csrf
        <x-form.input name="email" type="email" :label="__('auth.email')" autocomplete="username" required autofocus />
        <div>
            <div class="flex items-center justify-between">
                <label for="f-password" class="label">{{ __('auth.password_label') }}</label>
                <a href="{{ route('password.request') }}" class="mb-1.5 text-xs font-medium text-zinc-600 hover:text-zinc-900">{{ __('auth.forgot_password') }}</a>
            </div>
            <div class="relative">
                <input id="f-password" name="password" type="password" class="input pr-10" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
                <button type="button" class="absolute inset-y-0 right-0 grid w-10 place-items-center text-zinc-500" data-toggle-password="#f-password" aria-pressed="false" aria-label="{{ __('auth.show_password') }}"><x-icon name="eye" /></button>
            </div>
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <x-form.checkbox name="remember" :label="__('auth.remember_me')" />
        <button class="btn btn-dark w-full">{{ __('auth.sign_in') }}</button>
    </form>
@endsection

@section('below')
    @if ($registrationEnabled)
        {{ __('auth.no_account') }} <a href="{{ route('register') }}" class="font-semibold text-zinc-900 underline decoration-brand decoration-2 underline-offset-2">{{ __('auth.create_account') }}</a>
    @endif
@endsection
