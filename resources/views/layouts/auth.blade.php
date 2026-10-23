@extends('layouts.base')

@section('noindex', '1')
@section('body-class', 'min-h-full bg-zinc-50')

@section('body')
    <div class="flex min-h-screen flex-col">
        <header class="flex items-center justify-between px-4 py-4 sm:px-8">
            <x-logo />
            <x-lang-switcher />
        </header>
        <main id="main" class="flex flex-1 items-start justify-center px-4 pt-6 pb-16 sm:items-center sm:pt-0">
            <div class="w-full max-w-md">
                <div class="mb-6 text-center">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900">@yield('heading')</h1>
                    @hasSection('subheading')
                        <p class="mt-2 text-sm text-zinc-500">@yield('subheading')</p>
                    @endif
                </div>
                <div class="card p-6 sm:p-8">
                    <x-flash class="mb-5" :hide-errors="true" />
                    @yield('content')
                </div>
                @hasSection('below')
                    <div class="mt-6 text-center text-sm text-zinc-600">@yield('below')</div>
                @endif
            </div>
        </main>
        <footer class="px-4 pb-6 text-center text-xs text-zinc-500">
            <a href="{{ route('page.privacy') }}" class="hover:underline">{{ __('site.privacy') }}</a> ·
            <a href="{{ route('page.terms') }}" class="hover:underline">{{ __('site.terms') }}</a> ·
            <a href="{{ route('page.help') }}" class="hover:underline">{{ __('nav.help') }}</a>
        </footer>
    </div>
    @include('partials.cookie-notice')
@endsection
