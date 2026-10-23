@extends('layouts.auth')

@section('title', __('auth.forgot_password'))
@section('heading', __('auth.forgot_password'))
@section('subheading', __('auth.forgot_password_help'))

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4" novalidate>
        @csrf
        <x-form.input name="email" type="email" :label="__('auth.email')" autocomplete="email" required autofocus />
        <button class="btn btn-dark w-full">{{ __('auth.send_reset_link') }}</button>
    </form>
@endsection

@section('below')
    <a href="{{ route('login') }}" class="font-medium text-zinc-700 hover:text-zinc-900">← {{ __('auth.back_to_login') }}</a>
@endsection
