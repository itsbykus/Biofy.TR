@extends('layouts.site')

@push('head')
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $site['name'],
        'url' => url('/'),
        'description' => $site['description'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 md:py-24 lg:grid-cols-[1.1fr_0.9fr]">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-brand-soft px-3 py-1 text-xs font-semibold text-brand-ink"><x-icon name="sparkles" class="size-3.5" />{{ $site['tagline'] }}</p>
                <h1 class="mt-5 text-4xl leading-[1.05] font-black tracking-tight text-zinc-900 sm:text-6xl">{{ setting_text('home.hero_title') }}</h1>
                <p class="mt-5 max-w-xl text-lg text-zinc-600">{{ setting_text('home.hero_subtitle') }}</p>

                <form action="{{ route('register') }}" method="GET" class="mt-8 flex max-w-md flex-col gap-2 sm:flex-row">
                    <label for="claim" class="sr-only">{{ __('auth.username') }}</label>
                    <div class="flex flex-1 items-center rounded-xl border border-zinc-300 bg-white pl-3 focus-within:ring-2 focus-within:ring-brand">
                        <span class="text-sm text-zinc-500">{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}/</span>
                        <input id="claim" name="username" class="w-full border-0 bg-transparent px-1 py-3 text-sm focus:outline-none" placeholder="{{ __('site.home.claim_placeholder') }}" autocapitalize="none" spellcheck="false" maxlength="30">
                    </div>
                    <button class="btn btn-primary min-h-12 px-6">{{ setting_text('home.cta_label') }}</button>
                </form>
                <p class="mt-3 text-xs text-zinc-500">{{ __('site.home.no_card') }}</p>
            </div>

            {{-- Product preview: a real theme rendered as SVG --}}
            <div class="relative mx-auto w-full max-w-xs">
                <div class="absolute -inset-6 -z-10 rounded-[3rem] bg-brand/40 blur-2xl" aria-hidden="true"></div>
                <div class="overflow-hidden rounded-[2.2rem] border-[10px] border-ink bg-white shadow-2xl">
                    @php($hero = collect($themes)->first())
                    @if ($hero)
                        @include('partials.theme-thumb', ['t' => $hero['preview'], 'key' => 'hero'])
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="border-t border-zinc-100 bg-zinc-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="text-center text-3xl font-bold tracking-tight">{{ __('site.home.features_title') }}</h2>
            <p class="mx-auto mt-3 max-w-2xl text-center text-zinc-600">{{ __('site.home.features_text') }}</p>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (['palette', 'layers', 'chart-column', 'globe', 'shield-check', 'smartphone'] as $i => $icon)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
                        <span class="grid size-10 place-items-center rounded-xl bg-brand text-ink"><x-icon :name="$icon" class="size-5" /></span>
                        <h3 class="mt-4 font-semibold">{{ __('site.home.feature_'.$i.'_title') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('site.home.feature_'.$i.'_text') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Themes --}}
    <section>
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">{{ __('site.home.themes_title') }}</h2>
                    <p class="mt-2 text-zinc-600">{{ __('site.home.themes_text') }}</p>
                </div>
                <a href="{{ route('themes') }}" class="btn btn-secondary">{{ __('site.home.all_themes') }}</a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($themes as $key => $theme)
                    <figure class="overflow-hidden rounded-2xl border border-zinc-200">
                        @include('partials.theme-thumb', ['t' => $theme['preview'], 'key' => $key])
                        <figcaption class="px-3 py-2 text-xs font-semibold">{{ $theme['name'][app()->getLocale()] ?? $theme['name']['tr'] }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    @if ($packages->isNotEmpty())
        <section class="border-t border-zinc-100 bg-zinc-50">
            <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6">
                <h2 class="mb-10 text-center text-3xl font-bold tracking-tight">{{ __('site.pricing.title') }}</h2>
                @include('site.partials.pricing-cards')
            </div>
        </section>
    @endif

    <section class="bg-brand">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-5 px-4 py-14 text-center sm:px-6">
            <h2 class="text-3xl font-black tracking-tight text-ink">{{ __('site.home.cta_title') }}</h2>
            <a href="{{ route('register') }}" class="btn btn-dark min-h-12 px-8">{{ setting_text('home.cta_label') }}</a>
        </div>
    </section>
@endsection
