@extends('layouts.admin')

@section('title', __('admin.templates.names.'.$key))

@section('content')
    <a href="{{ route('admin.templates.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">← {{ __('admin.nav.templates') }}</a>
    <x-page-header class="mt-1" :title="__('admin.templates.names.'.$key).' ('.strtoupper($locale).')'" />

    <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
        <form method="POST" action="{{ route('admin.templates.update', [$key, $locale]) }}" class="card card-pad space-y-4" novalidate>
            @csrf @method('PUT')
            <x-form.input name="subject" :label="__('admin.templates.subject')" :value="$template->subject" maxlength="200" required />
            <x-form.textarea name="body" :label="__('admin.templates.body')" :value="$template->body" rows="12" required :help="__('admin.templates.body_help')" />
            <div class="flex justify-between gap-2">
                <button name="reset" value="1" class="btn btn-ghost" data-confirm="{{ __('admin.templates.reset_confirm') }}">{{ __('admin.templates.reset') }}</button>
                <button class="btn btn-dark">{{ __('common.save') }}</button>
            </div>
        </form>
        <aside class="card card-pad h-fit text-sm">
            <h2 class="card-title">{{ __('admin.templates.placeholders') }}</h2>
            <ul class="mt-3 space-y-1">
                @foreach ($placeholders as $placeholder)<li><code class="code">{{ '{'.$placeholder.'}' }}</code> — {{ __('admin.templates.vars.'.$placeholder) }}</li>@endforeach
            </ul>
            <p class="help mt-4">{{ __('admin.templates.safety') }}</p>
        </aside>
    </div>
@endsection
