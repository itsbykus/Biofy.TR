@extends('layouts.base')

@section('noindex', '1')
@section('body-class', 'min-h-full bg-zinc-50')

@php
    $user = auth()->user();
    $nav = [
        ['dashboard', 'layout-dashboard', __('nav.overview'), 'dashboard'],
        ['pages.index', 'layers', __('nav.pages'), 'pages.*'],
        ['pages.create', 'circle-plus', __('nav.new_page'), 'pages.create'],
        ['links.index', 'link', __('nav.links'), 'links.*'],
        ['dashboard.themes', 'palette', __('nav.themes'), 'dashboard.themes'],
        ['analytics.index', 'chart-column', __('nav.analytics'), 'analytics.*'],
        ['domains.index', 'globe', __('nav.domains'), 'domains.*'],
        ['media.index', 'images', __('nav.media'), 'media.*'],
    ];
    $accountNav = [
        ['account.edit', 'user', __('nav.account'), 'account.*'],
        ['security.index', 'shield', __('nav.security'), 'security.*'],
        ['api-tokens.index', 'key-round', __('nav.api'), 'api-tokens.*'],
        ['plan.show', 'package', __('nav.plan'), 'plan.*'],
        ['dashboard.help', 'circle-help', __('nav.help'), 'dashboard.help'],
    ];
@endphp

@section('body')
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 hidden w-72 overflow-y-auto border-r border-zinc-200 bg-white px-3 py-4 lg:sticky lg:top-0 lg:block lg:h-screen lg:w-auto" aria-label="{{ __('common.main_navigation') }}">
            <div class="flex items-center justify-between px-2">
                <x-logo :href="route('dashboard')" size="sm" />
                <button type="button" class="btn btn-ghost btn-icon lg:hidden" data-nav-toggle="#app-sidebar" aria-label="{{ __('common.close') }}"><x-icon name="x" /></button>
            </div>
            <nav class="mt-6 space-y-0.5">
                @foreach ($nav as [$route, $icon, $label, $pattern])
                    <a href="{{ route($route) }}" class="nav-link" @if (request()->routeIs($pattern) && ! ($route === 'pages.index' && request()->routeIs('pages.create'))) aria-current="page" @endif>
                        <x-icon :name="$icon" />{{ $label }}
                    </a>
                @endforeach
                <p class="nav-section">{{ __('nav.account_section') }}</p>
                @foreach ($accountNav as [$route, $icon, $label, $pattern])
                    <a href="{{ route($route) }}" class="nav-link" @if (request()->routeIs($pattern)) aria-current="page" @endif><x-icon :name="$icon" />{{ $label }}</a>
                @endforeach
                @if ($user->canAccessAdmin())
                    <p class="nav-section">{{ __('nav.staff') }}</p>
                    <a href="{{ route('admin.dashboard') }}" class="nav-link"><x-icon name="shield-check" />{{ __('nav.admin_panel') }}</a>
                @endif
            </nav>
            <div class="mt-6 border-t border-zinc-100 px-2 pt-4">
                <p class="truncate text-sm font-medium text-zinc-900">{{ $user->name }}</p>
                <p class="truncate text-xs text-zinc-500">{{ $user->email }}</p>
                <div class="mt-3 flex items-center justify-between">
                    <x-lang-switcher />
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm"><x-icon name="log-out" class="size-3.5" />{{ __('auth.sign_out') }}</button></form>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <div class="sticky top-0 z-30 flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-3 lg:hidden">
                <button type="button" class="btn btn-ghost btn-icon" data-nav-toggle="#app-sidebar" aria-controls="app-sidebar" aria-expanded="false" aria-label="{{ __('common.menu') }}"><x-icon name="menu" class="size-5" /></button>
                <x-logo :href="route('dashboard')" size="sm" />
                <span class="w-9"></span>
            </div>

            @if (! $user->hasVerifiedEmail())
                <div class="border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900 sm:px-8">
                    <form method="POST" action="{{ route('verification.send') }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <x-icon name="mail" />{{ __('auth.verify_banner') }}
                        <button class="font-semibold underline underline-offset-2">{{ __('auth.resend_verification') }}</button>
                    </form>
                </div>
            @endif

            <main id="main" class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-8 sm:py-8">
                <x-flash class="mb-6" :hide-errors="(bool) ($hideErrorSummary ?? false)" />
                @yield('content')
            </main>
        </div>
    </div>
@endsection
