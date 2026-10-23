@extends('layouts.auth')

@section('title', __('auth.two_factor'))
@section('heading', __('auth.two_factor'))
@section('subheading', __('auth.two_factor_help'))

@section('content')
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4" novalidate>
        @csrf
        <x-form.input name="code" :label="__('auth.authentication_code')" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="6" autofocus />
        <button class="btn btn-dark w-full">{{ __('auth.verify') }}</button>
    </form>
    <details class="mt-6 text-sm">
        <summary class="cursor-pointer font-medium text-zinc-700">{{ __('auth.use_recovery_code') }}</summary>
        <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-3 space-y-3" novalidate>
            @csrf
            <x-form.input name="recovery_code" :label="__('auth.recovery_code')" autocomplete="off" />
            <button class="btn btn-secondary w-full">{{ __('auth.verify') }}</button>
        </form>
    </details>
@endsection
