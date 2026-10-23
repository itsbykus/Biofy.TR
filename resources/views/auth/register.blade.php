@extends('layouts.auth')

@section('title', __('auth.create_account'))
@section('heading', setting_text('auth.register_title'))
@section('subheading', setting_text('auth.register_subtitle'))

@section('content')
    @if (! $registrationEnabled)
        <div class="alert alert-warning"><x-icon name="lock" class="mt-0.5" /><p>{{ __('auth.registration_closed') }}</p></div>
    @else
        @if ($googleEnabled)
            @include('auth.partials.google-button', ['label' => __('auth.sign_up_with_google')])
            <div class="my-5 flex items-center gap-3 text-xs text-zinc-400"><span class="h-px flex-1 bg-zinc-200"></span>{{ __('auth.or') }}<span class="h-px flex-1 bg-zinc-200"></span></div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4" novalidate>
            @csrf
            <x-form.input name="name" :label="__('auth.name')" autocomplete="name" required autofocus />
            <div>
                <label for="f-username" class="label">{{ __('auth.username') }}</label>
                <div class="flex rounded-lg focus-within:ring-2 focus-within:ring-brand">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-zinc-300 bg-zinc-50 px-3 text-sm text-zinc-500">{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}/</span>
                    <input id="f-username" name="username" value="{{ old('username', strtolower((string) request('username'))) }}" class="input rounded-l-none focus:ring-0" autocomplete="off" autocapitalize="none" spellcheck="false" required minlength="3" maxlength="30" pattern="[a-z0-9_-]+" aria-describedby="f-username-help" @error('username') aria-invalid="true" @enderror>
                </div>
                <p id="f-username-help" class="{{ $errors->has('username') ? 'error' : 'help' }}">{{ $errors->first('username') ?: __('auth.username_help') }}</p>
            </div>
            <x-form.input name="email" type="email" :label="__('auth.email')" autocomplete="email" required />
            <x-form.input name="password" type="password" :label="__('auth.password')" autocomplete="new-password" required :help="__('auth.password_rules', ['min' => max(8, (int) setting('security.password_min_length'))])" />
            <x-form.input name="password_confirmation" type="password" :label="__('auth.password_confirm')" autocomplete="new-password" required />
            <div class="flex items-start gap-3">
                <input id="terms" type="checkbox" name="terms" value="1" class="checkbox mt-0.5" required @checked(old('terms'))>
                <label for="terms" class="text-sm text-zinc-700">{!! __('auth.accept_terms', ['terms' => '<a href="'.route('page.terms').'" class="font-medium underline" target="_blank">'.e(__('site.terms')).'</a>', 'privacy' => '<a href="'.route('page.privacy').'" class="font-medium underline" target="_blank">'.e(__('site.privacy')).'</a>']) !!}</label>
            </div>
            @error('terms')<p class="error">{{ $message }}</p>@enderror
            <button class="btn btn-primary w-full">{{ __('auth.create_account') }}</button>
        </form>
    @endif
@endsection

@section('below')
    {{ __('auth.have_account') }} <a href="{{ route('login') }}" class="font-semibold text-zinc-900 underline decoration-brand decoration-2 underline-offset-2">{{ __('auth.sign_in') }}</a>
@endsection
