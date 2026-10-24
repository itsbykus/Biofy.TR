@extends('layouts.base')

@section('noindex', '1')
@section('body-class', 'min-h-full bg-zinc-100')

@php
    $user = auth()->user();
    $groups = [
        null => [
            ['admin.dashboard', 'layout-dashboard', __('admin.nav.dashboard'), 'admin.dashboard', 'admin.access'],
        ],
        __('admin.nav.group_users') => [
            ['admin.users.index', 'users', __('admin.nav.users'), 'admin.users.*', 'users.view'],
            ['admin.profiles.index', 'layers', __('admin.nav.profiles'), 'admin.profiles.*', 'users.view'],
            ['admin.messages.index', 'inbox', __('admin.nav.messages'), 'admin.messages.*', 'messages.view'],
        ],
        __('admin.nav.group_product') => [
            ['admin.packages.index', 'package', __('admin.nav.packages'), 'admin.packages.*', 'packages.manage'],
            ['admin.features.index', 'sliders-horizontal', __('admin.nav.features'), 'admin.features.*', 'packages.manage'],
            ['admin.themes.index', 'palette', __('admin.nav.themes'), 'admin.themes.*', 'packages.manage'],
            ['admin.domains.index', 'globe', __('admin.nav.domains'), 'admin.domains.*', 'domains.manage'],
            ['admin.media.index', 'images', __('admin.nav.media'), 'admin.media.*', 'media.manage'],
        ],
        __('admin.nav.group_content') => [
            ['admin.pages.index', 'file-text', __('admin.nav.pages'), 'admin.pages.*', 'content.manage'],
            ['admin.blog.index', 'newspaper', __('admin.nav.blog'), 'admin.blog.*', 'content.manage'],
            ['admin.menus.edit', 'list', __('admin.nav.menus'), 'admin.menus.*', 'content.manage'],
            ['admin.templates.index', 'mail', __('admin.nav.templates'), 'admin.templates.*', 'content.manage'],
            ['admin.announcement.create', 'megaphone', __('admin.nav.announcement'), 'admin.announcement.*', 'content.manage'],
            ['admin.reserved.index', 'lock', __('admin.nav.reserved'), 'admin.reserved.*', 'content.manage'],
        ],
        __('admin.nav.group_system') => [
            ['admin.settings.edit', 'settings', __('admin.nav.settings'), 'admin.settings.*', 'settings.manage', ['group' => 'general']],
            ['admin.backups.index', 'hard-drive', __('admin.nav.backups'), 'admin.backups.*', 'backups.manage'],
            ['admin.system.index', 'server', __('admin.nav.system'), 'admin.system.*', 'system.manage'],
            ['admin.audit.index', 'scroll-text', __('admin.nav.audit'), 'admin.audit.*', 'audit.view'],
            ['admin.security-events.index', 'shield', __('admin.nav.security_events'), 'admin.security-events.*', 'audit.view'],
        ],
    ];
@endphp

@section('body')
    <div class="min-h-screen lg:grid lg:grid-cols-[250px_1fr]">
        <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 hidden w-72 overflow-y-auto bg-ink px-3 py-4 text-zinc-300 lg:sticky lg:top-0 lg:block lg:h-screen lg:w-auto" aria-label="{{ __('admin.nav.label') }}">
            <div class="flex items-center justify-between px-2">
                {{-- Logo and title: Admin → Settings → Brand (panel logo, falls back to the site logo). --}}
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2 font-bold text-white">
                    @if ($site['admin']['logo'])
                        <img src="{{ $site['admin']['logo'] }}" alt="" class="h-7 w-auto max-w-[9rem] shrink-0 object-contain">
                    @else
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-brand text-sm font-black text-ink">b</span>
                    @endif
                    <span class="truncate">{{ $site['admin']['title'] }}</span>
                </a>
                <button type="button" class="rounded p-1 text-zinc-400 lg:hidden" data-nav-toggle="#admin-sidebar" aria-label="{{ __('common.close') }}"><x-icon name="x" /></button>
            </div>
            <nav class="mt-5 space-y-0.5">
                @foreach ($groups as $groupLabel => $items)
                    @php($visible = collect($items)->filter(fn ($i) => $user->hasPermission($i[4])))
                    @if ($visible->isNotEmpty())
                        @if ($groupLabel)<p class="mt-5 mb-1 px-3 text-[11px] font-semibold tracking-wider text-zinc-500 uppercase">{{ $groupLabel }}</p>@endif
                        @foreach ($visible as $item)
                            <a href="{{ route($item[0], $item[5] ?? []) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors', 'bg-white/10 text-white' => request()->routeIs($item[3]), 'hover:bg-white/5 hover:text-white' => ! request()->routeIs($item[3])]) @if (request()->routeIs($item[3])) aria-current="page" @endif>
                                <x-icon :name="$item[1]" />{{ $item[2] }}
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </nav>
            <div class="mt-6 border-t border-white/10 px-2 pt-4 text-xs">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-zinc-400 hover:text-white"><x-icon name="arrow-right" class="size-3.5" />{{ __('admin.nav.back_to_app') }}</a>
                <p class="mt-3 truncate text-zinc-400">{{ $user->email }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-zinc-500">v{{ config('biofy.version') }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-zinc-400 hover:text-white">{{ __('auth.sign_out') }}</button></form>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <div class="sticky top-0 z-30 flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-3 lg:hidden">
                <button type="button" class="btn btn-ghost btn-icon" data-nav-toggle="#admin-sidebar" aria-controls="admin-sidebar" aria-expanded="false" aria-label="{{ __('common.menu') }}"><x-icon name="menu" class="size-5" /></button>
                <span class="font-semibold">{{ $site['admin']['title'] }}</span>
                <x-lang-switcher />
            </div>
            @if (app(\App\Services\System\UpdateService::class)->isPending())
                <div class="border-b border-amber-300 bg-amber-100 px-4 py-2.5 text-sm text-amber-900 sm:px-8">
                    <x-icon name="triangle-alert" class="inline" /> {{ __('system.pending_banner') }}
                    <a href="{{ route('admin.system.index') }}" class="font-semibold underline">{{ __('system.open_update') }}</a>
                </div>
            @endif
            @if (setting('maintenance.enabled'))
                <div class="border-b border-sky-200 bg-sky-50 px-4 py-2 text-sm text-sky-900 sm:px-8">{{ __('admin.maintenance_on') }}</div>
            @endif
            <main id="main" class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-8 sm:py-8">
                <x-flash class="mb-6" />
                @yield('content')
            </main>
        </div>
    </div>
@endsection
