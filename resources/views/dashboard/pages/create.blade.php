@extends('layouts.app')

@section('title', __('nav.new_page'))

@section('content')
    <x-page-header :title="__('nav.new_page')" :description="__('profiles.create_help')" />

    <form method="POST" action="{{ route('pages.store') }}" class="card card-pad max-w-xl space-y-4" novalidate>
        @csrf
        <x-form.input name="display_name" :label="__('editor.profile.display_name')" :value="auth()->user()->name" maxlength="80" required autofocus />
        <div>
            <label for="f-slug" class="label">{{ __('editor.settings.slug') }}</label>
            <div class="flex rounded-lg focus-within:ring-2 focus-within:ring-brand">
                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-zinc-300 bg-zinc-50 px-3 text-sm text-zinc-500">{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}/</span>
                <input id="f-slug" name="slug" value="{{ old('slug') }}" class="input rounded-l-none focus:ring-0" autocapitalize="none" spellcheck="false" minlength="3" maxlength="30" required aria-describedby="slug-help" @error('slug') aria-invalid="true" @enderror>
            </div>
            <p id="slug-help" class="{{ $errors->has('slug') ? 'error' : 'help' }}">{{ $errors->first('slug') ?: __('auth.username_help') }}</p>
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('pages.index') }}" class="btn btn-ghost">{{ __('common.cancel') }}</a>
            <button class="btn btn-primary">{{ __('profiles.create') }}</button>
        </div>
    </form>
@endsection
