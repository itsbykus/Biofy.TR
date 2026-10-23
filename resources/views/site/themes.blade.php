@extends('layouts.site')

@section('title', __('site.themes.title'))
@section('description', __('site.themes.description'))

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h1 class="text-4xl font-black tracking-tight">{{ __('site.themes.title') }}</h1>
        <p class="mt-3 max-w-2xl text-lg text-zinc-600">{{ __('site.themes.description') }}</p>
        <div class="mt-10 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($themes as $key => $theme)
                <figure class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                    @include('partials.theme-thumb', ['t' => $theme['preview'], 'key' => $key])
                    <figcaption class="space-y-1 p-4">
                        <p class="flex items-center justify-between gap-2 font-semibold">{{ $theme['name'][app()->getLocale()] ?? $theme['name']['tr'] }} @if ($theme['is_premium'])<span class="badge badge-yellow">Pro</span>@endif</p>
                        <p class="text-xs text-zinc-500">{{ $theme['description'][app()->getLocale()] ?? $theme['description']['tr'] }}</p>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>
@endsection
