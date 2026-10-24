@extends('layouts.site')

@section('title', setting_text('blog.title'))
@section('description', setting_text('blog.description'))
{{-- Every page of the list is its own canonical URL (page 2 must not point to page 1). --}}
@section('canonical', $posts->onFirstPage() ? route('blog.index') : $posts->url($posts->currentPage()))

@push('head')
    @if ($posts->previousPageUrl())<link rel="prev" href="{{ $posts->previousPageUrl() }}">@endif
    @if ($posts->nextPageUrl())<link rel="next" href="{{ $posts->nextPageUrl() }}">@endif
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'Blog',
        'name' => setting_text('blog.title'),
        'description' => setting_text('blog.description') ?: null,
        'url' => route('blog.index'),
        'blogPost' => $posts->map(fn ($p) => ['@type' => 'BlogPosting', 'headline' => $p->seoTitle(), 'url' => $p->url(), 'datePublished' => $p->published_at->toIso8601String()])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <section class="relative isolate overflow-hidden border-b border-zinc-100 bg-white">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="landing-grid absolute inset-0"></div>
            <div class="absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-brand/25 blur-3xl"></div>
        </div>
        <div class="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6 md:py-20">
            <p class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3.5 py-1.5 text-xs font-semibold text-zinc-800 ring-1 ring-zinc-200 ring-inset backdrop-blur">
                <span class="grid size-5 place-items-center rounded-full bg-brand text-ink"><x-icon name="newspaper" class="size-3" /></span>{{ $site['name'] }}
            </p>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-balance sm:text-6xl">{{ setting_text('blog.title') }}</h1>
            @if (setting_text('blog.description'))
                <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-600 text-pretty">{{ setting_text('blog.description') }}</p>
            @endif
        </div>
    </section>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
            @if ($posts->isEmpty())
                <x-empty icon="newspaper" :title="__('site.blog.empty')" :text="__('site.blog.empty_text')" />
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        @include('site.blog._card', ['post' => $post, 'featured' => $loop->first && $posts->onFirstPage()])
                    @endforeach
                </div>
                <div class="mt-10">{{ $posts->links() }}</div>
            @endif
        </div>
    </section>
@endsection
