@extends('layouts.site')

@push('head')
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode([
        '@'.'context' => 'https://schema.org', // split so Blade does not compile it as its context directive
        '@type' => 'WebSite',
        'name' => $site['name'],
        'url' => url('/'),
        'description' => $site['description'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@php
    // Everything editable lives in Admin → Settings → Home page content.
    $style = in_array(setting('home.hero_style'), ['glow', 'dark', 'minimal'], true) ? setting('home.hero_style') : 'glow';
    $dark = $style === 'dark';
    $secondaryLabel = setting_text('home.secondary_cta_label');
    $secondaryUrl = setting('home.secondary_cta_url') ? safe_internal_url(setting('home.secondary_cta_url')) : null;
    $host = preg_replace('#^https?://#', '', rtrim(config('app.url'), '/'));
@endphp

@section('content')
    {{-- Hero --}}
    <section @class(['relative isolate overflow-hidden', 'landing-dark bg-ink text-white' => $dark, 'bg-white' => ! $dark])>
        @if ($style !== 'minimal')
            <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
                <div class="landing-grid absolute inset-0"></div>
                <div @class(['absolute -top-48 left-1/2 size-[46rem] -translate-x-1/2 rounded-full blur-3xl', 'bg-brand/30' => ! $dark, 'bg-brand/20' => $dark])></div>
                <div @class(['absolute top-1/3 -right-32 size-[26rem] rounded-full blur-3xl', 'bg-sky-200/50' => ! $dark, 'bg-sky-500/15' => $dark])></div>
            </div>
        @endif

        <div class="mx-auto grid max-w-6xl items-center gap-14 px-4 pt-16 pb-20 sm:px-6 md:pt-24 md:pb-28 lg:grid-cols-[1.05fr_0.95fr]">
            <div>
                <p @class(['inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset', 'bg-white/80 text-zinc-800 ring-zinc-200 backdrop-blur' => ! $dark, 'bg-white/10 text-white ring-white/15' => $dark])>
                    <span class="grid size-5 place-items-center rounded-full bg-brand text-ink"><x-icon name="sparkles" class="size-3" /></span>
                    {{ setting_text('home.badge') ?: $site['tagline'] }}
                </p>
                <h1 class="mt-6 text-[2.6rem] leading-[1.02] font-black tracking-tight text-balance sm:text-6xl lg:text-7xl">{{ setting_text('home.hero_title') }}</h1>
                <p @class(['mt-6 max-w-xl text-lg leading-relaxed text-pretty', 'text-zinc-600' => ! $dark, 'text-zinc-300' => $dark])>{{ setting_text('home.hero_subtitle') }}</p>

                <form action="{{ route('register') }}" method="GET" @class(['mt-9 flex max-w-lg flex-col gap-2 rounded-2xl p-2 sm:flex-row sm:items-center', 'bg-white shadow-xl shadow-zinc-900/5 ring-1 ring-zinc-200' => ! $dark, 'bg-white/10 ring-1 ring-white/15' => $dark])>
                    <label for="claim" class="sr-only">{{ __('auth.username') }}</label>
                    <div class="flex flex-1 items-center rounded-xl pl-3 focus-within:ring-2 focus-within:ring-brand">
                        <span @class(['shrink-0 text-sm', 'text-zinc-500' => ! $dark, 'text-zinc-400' => $dark])>{{ $host }}/</span>
                        <input id="claim" name="username" @class(['w-full min-w-0 border-0 bg-transparent px-1 py-3 text-sm font-medium focus:outline-none', 'placeholder:text-zinc-500' => $dark]) placeholder="{{ __('site.home.claim_placeholder') }}" autocapitalize="none" spellcheck="false" maxlength="30">
                    </div>
                    <button class="btn btn-primary min-h-12 px-6 whitespace-nowrap">{{ setting_text('home.cta_label') }}<x-icon name="arrow-right" class="size-4" /></button>
                </form>

                <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                    @if ($secondaryLabel && $secondaryUrl)
                        <a href="{{ $secondaryUrl }}" @class(['group inline-flex items-center gap-1.5 font-semibold', 'text-zinc-900' => ! $dark, 'text-white' => $dark])>
                            {{ $secondaryLabel }}<x-icon name="chevron-right" class="size-4 transition group-hover:translate-x-0.5" />
                        </a>
                    @endif
                    @foreach ([0, 1, 2] as $i)
                        <span @class(['inline-flex items-center gap-1.5', 'text-zinc-500' => ! $dark, 'text-zinc-400' => $dark])><x-icon name="circle-check" class="size-4 text-emerald-500" />{{ __('site.home.hero_point_'.$i) }}</span>
                    @endforeach
                </div>
            </div>

            {{-- Product preview: a sample bio page in a phone (or the uploaded hero image) --}}
            @include('site.partials.hero-mockup')
        </div>
    </section>

    {{-- How it works --}}
    @if (setting('home.show_steps'))
        <section class="border-t border-zinc-100 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold tracking-wide text-zinc-500 uppercase">{{ __('site.home.steps_kicker') }}</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('site.home.steps_title') }}</h2>
                </div>
                <ol class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach (['user', 'palette', 'share-2'] as $i => $icon)
                        <li class="relative rounded-3xl border border-zinc-200 bg-zinc-50/60 p-7">
                            <span class="absolute top-6 right-7 text-5xl font-black text-zinc-200 tabular-nums" aria-hidden="true">{{ $i + 1 }}</span>
                            <span class="grid size-11 place-items-center rounded-2xl bg-ink text-white"><x-icon :name="$icon" class="size-5" /></span>
                            <h3 class="mt-5 text-lg font-semibold">{{ __('site.home.step_'.$i.'_title') }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">{{ __('site.home.step_'.$i.'_text') }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    {{-- Features --}}
    @if (setting('home.show_features'))
        <section class="border-t border-zinc-100 bg-zinc-50">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ setting_text('home.features_title') }}</h2>
                    <p class="mt-4 text-lg text-zinc-600 text-pretty">{{ setting_text('home.features_text') }}</p>
                </div>
                <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (['palette', 'layers', 'chart-column', 'globe', 'shield-check', 'smartphone'] as $i => $icon)
                        <div @class(['group rounded-3xl border p-7 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-zinc-900/5', 'border-ink bg-ink text-white' => $i === 0, 'border-zinc-200 bg-white' => $i !== 0])>
                            <span @class(['grid size-11 place-items-center rounded-2xl', 'bg-brand text-ink' => $i === 0, 'bg-brand-soft text-brand-ink' => $i !== 0])><x-icon :name="$icon" class="size-5" /></span>
                            <h3 class="mt-5 font-semibold">{{ __('site.home.feature_'.$i.'_title') }}</h3>
                            <p @class(['mt-1.5 text-sm leading-relaxed', 'text-zinc-300' => $i === 0, 'text-zinc-600' => $i !== 0])>{{ __('site.home.feature_'.$i.'_text') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Themes --}}
    @if (setting('home.show_themes') && $themes)
        <section class="bg-white">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('site.home.themes_title') }}</h2>
                        <p class="mt-3 text-zinc-600">{{ __('site.home.themes_text') }}</p>
                    </div>
                    <a href="{{ route('themes') }}" class="btn btn-secondary">{{ __('site.home.all_themes') }}<x-icon name="arrow-right" class="size-4" /></a>
                </div>
                <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($themes as $key => $theme)
                        <figure class="group">
                            <div class="overflow-hidden rounded-[1.4rem] border-[6px] border-zinc-900 shadow-md transition duration-300 group-hover:-translate-y-1 group-hover:shadow-xl">
                                @include('partials.theme-thumb', ['t' => $theme['preview'], 'key' => $key])
                            </div>
                            <figcaption class="mt-2.5 text-center text-xs font-semibold text-zinc-700">{{ $theme['name'][app()->getLocale()] ?? $theme['name']['tr'] }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Pricing --}}
    @if (setting('home.show_pricing') && $packages->isNotEmpty())
        <section class="border-t border-zinc-100 bg-zinc-50">
            <div class="mx-auto max-w-5xl px-4 py-20 sm:px-6">
                <h2 class="mb-12 text-center text-3xl font-bold tracking-tight sm:text-4xl">{{ __('site.pricing.title') }}</h2>
                @include('site.partials.pricing-cards')
            </div>
        </section>
    @endif

    {{-- Closing call to action --}}
    <section class="bg-white px-4 py-16 sm:px-6">
        <div class="relative isolate mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-ink px-6 py-16 text-center text-white sm:px-12">
            <div class="pointer-events-none absolute -top-32 left-1/2 -z-10 size-[34rem] -translate-x-1/2 rounded-full bg-brand/25 blur-3xl" aria-hidden="true"></div>
            <h2 class="mx-auto max-w-2xl text-3xl font-black tracking-tight text-balance sm:text-5xl">{{ setting_text('home.cta_title') }}</h2>
            @if (setting_text('home.cta_text'))
                <p class="mx-auto mt-4 max-w-xl text-lg text-zinc-300 text-pretty">{{ setting_text('home.cta_text') }}</p>
            @endif
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="btn btn-primary min-h-12 px-8">{{ setting_text('home.cta_label') }}<x-icon name="arrow-right" class="size-4" /></a>
                @if ($secondaryLabel && $secondaryUrl)
                    <a href="{{ $secondaryUrl }}" class="btn min-h-12 px-6 text-white ring-1 ring-white/25 hover:bg-white/10">{{ $secondaryLabel }}</a>
                @endif
            </div>
        </div>
    </section>
@endsection
