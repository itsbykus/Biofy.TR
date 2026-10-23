@extends('layouts.auth')

@section('title', __('auth.reset_password'))
@section('heading', __('auth.reset_password'))

@section('content')
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" :label="__('auth.email')" :value="$email" autocomplete="username" required />
        <x-form.input name="password" type="password" :label="__('auth.new_password')" autocomplete="new-password" required autofocus :help="__('auth.password_rules', ['min' => max(8, (int) setting('security.password_min_length'))])" />
        <x-form.input name="password_confirmation" type="password" :label="__('auth.password_confirm')" autocomplete="new-password" required />
        <button class="btn btn-dark w-full">{{ __('auth.reset_password') }}</button>
    </form>
@endsection
