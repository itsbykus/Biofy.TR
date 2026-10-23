@extends('layouts.auth')

@section('title', __('auth.verify_email'))
@section('heading', __('auth.verify_email'))

@section('content')
    <div class="space-y-4 text-sm text-zinc-600">
        <p>{{ __('auth.verify_email_text', ['email' => auth()->user()->email]) }}</p>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-dark w-full">{{ __('auth.resend_verification') }}</button>
        </form>
        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('account.edit') }}" class="font-medium text-zinc-700 hover:text-zinc-900">{{ __('auth.change_email') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-medium text-zinc-700 hover:text-zinc-900">{{ __('auth.sign_out') }}</button></form>
        </div>
    </div>
@endsection
