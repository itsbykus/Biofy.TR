@extends('layouts.auth')

@section('title', __('auth.complete_signup'))
@section('heading', __('auth.complete_signup'))
@section('subheading', __('auth.complete_signup_help', ['email' => $pending['email']]))

@section('content')
    <form method="POST" action="{{ route('auth.google.finish') }}" class="space-y-4" novalidate>
        @csrf
        <x-form.input name="name" :label="__('auth.name')" :value="$pending['name']" required />
        <x-form.input name="username" :label="__('auth.username')" :value="$suggestion" :help="__('auth.username_help')" autocapitalize="none" spellcheck="false" required />
        <div class="flex items-start gap-3">
            <input id="terms" type="checkbox" name="terms" value="1" class="checkbox mt-0.5" required>
            <label for="terms" class="text-sm text-zinc-700">{!! __('auth.accept_terms', ['terms' => '<a href="'.route('page.terms').'" class="font-medium underline" target="_blank">'.e(__('site.terms')).'</a>', 'privacy' => '<a href="'.route('page.privacy').'" class="font-medium underline" target="_blank">'.e(__('site.privacy')).'</a>']) !!}</label>
        </div>
        @error('terms')<p class="error">{{ $message }}</p>@enderror
        <button class="btn btn-primary w-full">{{ __('auth.create_account') }}</button>
    </form>
@endsection
