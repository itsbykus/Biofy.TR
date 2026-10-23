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

    <header class="sticky top-0 z-30 border-b border-zinc-100 bg-white/90 text-zinc-900 backdrop-blur">
        <nav class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-4 px-4 py-2 sm:px-6" aria-label="{{ __('common.main_navigation') }}">
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

    @php
        $f = $site['footer'];
        // Admin → Settings → Footer → style
        $tone = [
            'light' => ['wrap' => 'border-t border-zinc-100 bg-zinc-50 text-zinc-900', 'muted' => 'text-zinc-500', 'link' => 'text-zinc-600 hover:text-zinc-900', 'line' => 'border-zinc-200', 'chip' => 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:text-zinc-900 hover:ring-zinc-300'],
            'dark' => ['wrap' => 'bg-ink text-white', 'muted' => 'text-zinc-400', 'link' => 'text-zinc-300 hover:text-white', 'line' => 'border-white/10', 'chip' => 'bg-white/10 text-white ring-1 ring-white/15 hover:bg-white/20'],
            'brand' => ['wrap' => 'bg-brand text-ink', 'muted' => 'text-ink/70', 'link' => 'text-ink/80 hover:text-ink', 'line' => 'border-ink/15', 'chip' => 'bg-ink/10 text-ink ring-1 ring-ink/15 hover:bg-ink/20'],
        ][$f['style']];
    @endphp
    <footer class="{{ $tone['wrap'] }}">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.6fr_1fr_1fr]">
            <div>
                <x-logo variant="footer" />
                <p class="mt-4 max-w-sm text-sm leading-relaxed {{ $tone['muted'] }}">{{ $site['footer_text'] ?: $site['tagline'] }}</p>
                @if ($f['show_social'] && $site['social'])
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($site['social'] as $platform => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full transition {{ $tone['chip'] }}" aria-label="{{ __('social.platforms.'.$platform) }}"><x-brand-icon :platform="$platform" class="size-4" /></a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            @if ($site['header_menu'])
                <nav aria-label="{{ $f['product_title'] }}">
                    <h2 class="text-sm font-semibold">{{ $f['product_title'] }}</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($site['header_menu'] as $item)
                            <li><a href="{{ $item['url'] }}" class="{{ $tone['link'] }}" @if ($item['external']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
            @if ($site['footer_menu'])
                <nav aria-label="{{ $f['company_title'] }}">
                    <h2 class="text-sm font-semibold">{{ $f['company_title'] }}</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($site['footer_menu'] as $item)
                            <li><a href="{{ $item['url'] }}" class="{{ $tone['link'] }}" @if ($item['external']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
        <div class="border-t {{ $tone['line'] }}">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-5 text-xs sm:flex-row sm:px-6 {{ $tone['muted'] }}">
                <p>{{ $f['copyright'] }}</p>
                <x-lang-switcher :tone="$f['style'] === 'dark' ? 'dark' : 'light'" />
            </div>
        </div>
    </footer>

    @include('partials.cookie-notice')
@endsection
