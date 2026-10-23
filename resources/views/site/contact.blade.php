@extends('layouts.site')

@section('title', __('contact.title'))
@section('description', __('contact.description'))

@section('content')
    <section class="mx-auto grid max-w-5xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1fr_1.2fr]">
        <div>
            <h1 class="text-4xl font-black tracking-tight">{{ $page?->translate('title') ?? __('contact.title') }}</h1>
            <p class="mt-4 text-zinc-600">{{ $page?->translate('body') ?? __('contact.description') }}</p>
            @if ($supportEmail)
                <p class="mt-6 flex items-center gap-2 text-sm"><x-icon name="mail" /><a href="mailto:{{ $supportEmail }}" class="font-medium underline">{{ $supportEmail }}</a></p>
            @endif
        </div>

        <form method="POST" action="{{ route('contact.store') }}" class="card card-pad space-y-4" novalidate>
            @csrf
            <x-flash />
            {{-- Honeypot for bots (visually hidden, not focusable) --}}
            <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="name" :label="__('contact.name')" :value="auth()->user()?->name" required />
                <x-form.input name="email" type="email" :label="__('contact.email')" :value="auth()->user()?->email" required />
            </div>
            <x-form.select name="package_id" :label="__('contact.package')" :value="$package?->id" :placeholder="__('contact.no_package')" :options="$packages->mapWithKeys(fn ($p) => [$p->id => $p->translate('name')])->all()" />
            <x-form.input name="subject" :label="__('contact.subject')" :value="$package ? __('contact.package_subject', ['package' => $package->translate('name')]) : ''" required />
            <x-form.textarea name="message" :label="__('contact.message')" rows="5" required />
            <p class="text-xs text-zinc-500">{!! __('contact.privacy', ['link' => '<a class="underline" href="'.route('page.privacy').'">'.e(__('site.privacy')).'</a>']) !!}</p>
            <button class="btn btn-dark w-full">{{ __('contact.send') }}</button>
        </form>
    </section>
@endsection
