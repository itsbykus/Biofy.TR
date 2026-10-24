<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-svg-error="{{ __('media.svg_failed') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = trim($__env->yieldContent('title'));
        $fullTitle = $pageTitle !== '' ? $pageTitle.' · '.$site['name'] : setting_text('seo.default_title');
        $description = trim($__env->yieldContent('description')) ?: setting_text('seo.default_description');
        $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
        // Pages may provide their own share image (e.g. a blog post cover).
        $ogImage = trim($__env->yieldContent('og_image')) ?: ($site['og_image'] ? url($site['og_image']) : null);
    @endphp
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($description, 300) }}">
    @hasSection('noindex')
        <meta name="robots" content="noindex, nofollow">
    @elseif (! setting('seo.allow_indexing'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:site_name" content="{{ $site['name'] }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($description, 300) }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'tr' ? 'tr_TR' : 'en_US' }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($description, 200) }}">
    {{-- Webmaster tools ownership (Admin -> Settings -> SEO); values are validated [A-Za-z0-9_-]. --}}
    @foreach (['google-site-verification' => 'seo.google_verification', 'msvalidate.01' => 'seo.bing_verification', 'yandex-verification' => 'seo.yandex_verification'] as $metaName => $settingKey)
        @if (setting($settingKey))
            <meta name="{{ $metaName }}" content="{{ setting($settingKey) }}">
        @endif
    @endforeach
    <meta name="theme-color" content="{{ $site['primary'] ?: '#FFCF00' }}">
    @if ($site['favicon'])
        <link rel="icon" type="image/png" href="{{ $site['favicon'] }}">
        <link rel="apple-touch-icon" href="{{ $site['favicon'] }}">
    @else
        <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23FFCF00'/%3E%3Ctext x='16' y='23' font-family='Arial' font-weight='900' font-size='20' text-anchor='middle' fill='%23111'%3Eb%3C/text%3E%3C/svg%3E">
    @endif
    {{-- Logo heights from the settings (clamped integers, see LayoutComposer). --}}
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">:root{--logo-h:{{ (int) ($site['logo_height'] ?? 32) }}px;--footer-logo-h:{{ (int) ($site['footer']['logo_height'] ?? 28) }}px}</style>
    @stack('head')
    @vite(array_merge(['resources/css/app.css', 'resources/js/app.js'], $extraAssets ?? []))
</head>
<body class="@yield('body-class', 'min-h-full bg-zinc-50')">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-ink focus:px-4 focus:py-2 focus:text-white">{{ __('common.skip_to_content') }}</a>
    @yield('body')
    @stack('scripts')
</body>
</html>
