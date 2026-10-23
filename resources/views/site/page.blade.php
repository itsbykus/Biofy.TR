@extends('layouts.site')

@section('title', $page->translate('title'))
@section('description', $page->translate('meta_description') ?? '')

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        <h1 class="text-4xl font-black tracking-tight">{{ $page->translate('title') }}</h1>
        <p class="mt-2 text-xs text-zinc-500">{{ __('site.last_updated', ['date' => local_time($page->updated_at, 'd.m.Y')]) }}</p>
        {{-- Rendered from Markdown with raw HTML escaped (see SiteController::page). --}}
        <div class="prose-biofy mt-8">{!! $body !!}</div>
    </article>
@endsection
