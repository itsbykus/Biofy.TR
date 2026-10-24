@extends('layouts.site')

@section('title', $post->seoTitle())
@section('description', $post->seoDescription())
@section('og_type', 'article')
@section('canonical', $post->canonical())
@if ($post->shareImageUrl())
    @section('og_image', $post->shareImageUrl())
@endif
@if ($post->seo_noindex)
    @section('noindex', '1')
@endif

@push('head')
    <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode(array_filter([
        '@'.'context' => 'https://schema.org', // split so Blade does not compile it as its context directive
        '@type' => 'BlogPosting',
        'headline' => $post->seoTitle(),
        'description' => $post->seoDescription() ?: null,
        'image' => $post->shareImageUrl(),
        'datePublished' => $post->published_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'mainEntityOfPage' => $post->canonical(),
        'publisher' => array_filter(['@type' => 'Organization', 'name' => $site['name'], 'logo' => $site['logo'] ? url($site['logo']) : null]),
        'author' => ['@type' => 'Organization', 'name' => $site['name'], 'url' => url('/')],
        'inLanguage' => app()->getLocale(),
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $site['name'], 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => setting_text('blog.title'), 'item' => route('blog.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $post->translate('title'), 'item' => $post->url()],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <article>
        <header class="relative isolate overflow-hidden bg-white">
            <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
                <div class="landing-grid absolute inset-0"></div>
                <div class="absolute -top-48 left-1/2 size-[40rem] -translate-x-1/2 rounded-full bg-brand/20 blur-3xl"></div>
            </div>
            <div class="mx-auto max-w-3xl px-4 pt-12 pb-10 sm:px-6 md:pt-16">
                <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 hover:text-zinc-900"><x-icon name="arrow-left" class="size-4" />{{ setting_text('blog.title') }}</a>
                <h1 class="mt-6 text-4xl leading-[1.08] font-black tracking-tight text-balance sm:text-5xl">{{ $post->translate('title') }}</h1>
                @if ($post->translate('excerpt'))
                    <p class="mt-5 text-xl leading-relaxed text-zinc-600 text-pretty">{{ $post->translate('excerpt') }}</p>
                @endif
                <p class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500">
                    <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="size-4" /><time datetime="{{ $post->published_at->toIso8601String() }}">{{ local_time($post->published_at, 'd F Y') }}</time></span>
                    <span aria-hidden="true">·</span>
                    <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="size-4" />{{ __('site.blog.reading_time', ['minutes' => $post->readingMinutes()]) }}</span>
                </p>
            </div>
            @if ($post->cover)
                <div class="mx-auto max-w-5xl px-4 sm:px-6">
                    <img src="{{ $post->cover->url() }}" alt="{{ $post->translate('title') }}" class="aspect-[16/9] w-full rounded-[2rem] object-cover shadow-2xl shadow-zinc-900/10" fetchpriority="high">
                </div>
            @endif
        </header>

        {{-- Rendered from Markdown with raw HTML escaped (see Site\BlogController::show). --}}
        <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <div class="prose-biofy prose-blog">{!! $body !!}</div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-zinc-100 bg-zinc-50">
            <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
                <h2 class="text-2xl font-bold tracking-tight">{{ __('site.blog.more') }}</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        @include('site.blog._card', ['post' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="bg-white px-4 py-16 sm:px-6">
        <div class="relative isolate mx-auto max-w-5xl overflow-hidden rounded-[2rem] bg-ink px-6 py-14 text-center text-white sm:px-12">
            <div class="pointer-events-none absolute -top-32 left-1/2 -z-10 size-[30rem] -translate-x-1/2 rounded-full bg-brand/25 blur-3xl" aria-hidden="true"></div>
            <h2 class="mx-auto max-w-xl text-3xl font-black tracking-tight text-balance">{{ setting_text('home.cta_title') }}</h2>
            <a href="{{ route('register') }}" class="btn btn-primary mt-7 min-h-12 px-8">{{ setting_text('home.cta_label') }}<x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </section>
@endsection
