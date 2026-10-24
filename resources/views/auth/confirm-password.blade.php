@extends('layouts.auth')

@section('title', __('auth.confirm_identity'))
@section('heading', __('auth.confirm_identity'))
@section('subheading', __('auth.confirm_identity_help'))

@section('content')
    @if ($user->hasPassword())
        <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4" novalidate>
            @csrf
            <x-form.input name="password" type="password" :label="__('auth.password_label')" autocomplete="current-password" required autofocus />
            <button class="btn btn-dark w-full">{{ __('auth.confirm') }}</button>
        </form>
    @elseif ($user->hasTwoFactorEnabled())
        <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4" novalidate>
            @csrf
            <x-form.input name="code" :label="__('auth.authentication_code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus />
            <button class="btn btn-dark w-full">{{ __('auth.confirm') }}</button>
        </form>
    @else
        <p class="mb-4 text-sm text-zinc-600">{{ __('auth.confirm_with_google_help') }}</p>
        @include('auth.partials.google-button', ['label' => __('auth.confirm_with_google'), 'href' => route('auth.google.redirect', ['intent' => 'confirm'])])
    @endif
@endsection

@section('below')
    <a href="{{ url()->previous() }}" class="font-medium text-zinc-700 hover:text-zinc-900">← {{ __('common.cancel') }}</a>
@endsection
