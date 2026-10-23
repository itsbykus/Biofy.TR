@extends('layouts.base')

@section('body-class', 'min-h-full bg-white')

@section('body')
    @if ($site['announcement'])
        <div @class(['px-4 py-2 text-center text-sm font-medium', 'bg-brand text-ink' => $site['announcement']['style'] === 'brand', 'bg-sky-600 text-white' => $site['announcement']['style'] === 'info', 'bg-amber-500 text-ink' => $site['announcement']['style'] === 'warning'])>
            @if ($site['announcement']['url'])
                <a href="{{ $site['announcement']['url'] }}" class="underline underline-offset-2">{{ $site['announcement']['text'] }}</a>
            @else
                {{ $site['announcement']['text'] }}
            @endif
        </div>
    @endif

    <header class="sticky top-0 z-30 border-b border-zinc-100 bg-white/90 backdrop-blur">
        <nav class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6" aria-label="{{ __('common.main_navigation') }}">
            <x-logo />
            <ul class="hidden items-center gap-1 md:flex">
                @foreach ($site['header_menu'] as $item)
                    <li><a href="{{ $item['url'] }}" class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900" @if ($item['external']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex items-center gap-2">
                <x-lang-switcher class="hidden sm:flex" />
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-dark">{{ __('nav.dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost hidden sm:inline-flex">{{ __('auth.sign_in') }}</a>
                    @if ($site['registration'])
                        <a href="{{ route('register') }}" class="btn btn-primary">{{ __('auth.get_started') }}</a>
                    @endif
                @endauth
                <button type="button" class="btn btn-ghost btn-icon md:hidden" data-nav-toggle="#mobile-nav" aria-expanded="false" aria-controls="mobile-nav" aria-label="{{ __('common.menu') }}"><x-icon name="menu" class="size-5" /></button>
            </div>
        </nav>
        <div id="mobile-nav" class="border-t border-zinc-100 px-4 pb-4 md:hidden" hidden>
            <ul class="mt-2 space-y-1">
                @foreach ($site['header_menu'] as $item)
                    <li><a href="{{ $item['url'] }}" class="nav-link">{{ $item['label'] }}</a></li>
                @endforeach
                @guest
                    <li><a href="{{ route('login') }}" class="nav-link">{{ __('auth.sign_in') }}</a></li>
                @endguest
            </ul>
            <x-lang-switcher class="mt-3" />
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    <footer class="border-t border-zinc-100 bg-zinc-50">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <x-logo size="sm" />
                <p class="mt-3 max-w-sm text-sm text-zinc-500">{{ $site['footer_text'] ?: $site['tagline'] }}</p>
                @if ($site['social'])
                    <ul class="mt-4 flex gap-2">
                        @foreach ($site['social'] as $platform => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full bg-white text-zinc-700 ring-1 ring-zinc-200 hover:text-zinc-900"><x-brand-icon :platform="$platform" class="size-4" /></a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <nav aria-label="{{ __('common.footer_navigation') }}">
                <h2 class="text-sm font-semibold text-zinc-900">{{ __('site.footer.product') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($site['header_menu'] as $item)
                        <li><a href="{{ $item['url'] }}" class="text-zinc-600 hover:text-zinc-900">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <nav aria-label="{{ __('site.footer.company') }}">
                <h2 class="text-sm font-semibold text-zinc-900">{{ __('site.footer.company') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($site['footer_menu'] as $item)
                        <li><a href="{{ $item['url'] }}" class="text-zinc-600 hover:text-zinc-900">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </div>
        <div class="border-t border-zinc-200">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-5 text-xs text-zinc-500 sm:flex-row sm:px-6">
                <p>© {{ date('Y') }} {{ $site['name'] }}. {{ __('site.footer.rights') }}</p>
                <x-lang-switcher />
            </div>
        </div>
    </footer>

    @include('partials.cookie-notice')
@endsection
