@php
    /** @var array $settings resolved theme settings (validated) */
    $s = $settings;
    $bodyClasses = implode(' ', [
        'bp', 't-'.$theme['key'], 'l-'.$theme['layout'], 'm-'.$s['mode'], 'bs-'.$s['button_style'], 'sh-'.$s['button_shadow'],
        'cs-'.$s['card_style'], 'av-'.$s['avatar_shape'], 'bn-'.($p['banner'] ? $s['banner_style'] : 'none'), 'ic-'.$s['icon_style'],
        'w-'.$s['page_width'], 'al-'.$s['align'], 'sp-'.$s['spacing'], 'an-'.$s['animation'], 'bg-'.$s['bg_type'],
    ]);
    $nonce = \Illuminate\Support\Facades\Vite::cspNonce();
    $initials = mb_strtoupper(mb_substr(trim($p['display_name']), 0, 1));
@endphp
<!DOCTYPE html>
<html lang="{{ $p['language'] ?? 'tr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($seo['description'], 300) }}">
    @if ($seo['noindex'])
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($seo['description'], 300) }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    @if ($seo['image'])
        <meta property="og:image" content="{{ url($seo['image']) }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ url($seo['image']) }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="theme-color" content="{{ $s['bg_color'] }}">
    @if ($p['avatar'])
        <link rel="icon" href="{{ $p['avatar'] }}">
    @endif
    <style nonce="{{ $nonce }}">:root{ {!! $cssVariables !!} }</style>
    @vite(array_merge(['resources/css/profile.css', 'resources/js/profile.js'], array_map(fn ($f) => 'resources/css/fonts/'.$f.'.css', $fonts)))
    @unless ($seo['noindex'])
        <script type="application/ld+json" nonce="{{ $nonce }}">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'url' => $seo['canonical'],
            'name' => $seo['title'],
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $p['display_name'],
                'jobTitle' => $p['title'] ?: null,
                'description' => $p['bio_short'] ?: null,
                'image' => $p['avatar'] ? url($p['avatar']) : null,
                'sameAs' => array_values(array_column($socialLinks, 'url')) ?: null,
            ]),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endunless
</head>
<body class="{{ $bodyClasses }}">
    @if ($preview)
        <div class="bp-preview-bar" role="status">{{ __('profile.preview_bar') }}</div>
    @endif

    <main class="bp-page">
        @unless ($preview)
            <button type="button" class="bp-share" data-share="{{ $seo['canonical'] }}" data-copied="{{ __('profile.link_copied') }}" aria-label="{{ __('profile.share') }}"><x-icon name="share-2" /></button>
        @endunless

        <header class="bp-head">
            @if ($p['banner'])
                <div class="bp-banner"><img src="{{ $p['banner'] }}" alt="" fetchpriority="high"></div>
            @endif

            @if ($p['avatar'])
                <img class="bp-avatar" src="{{ $p['avatar'] }}" alt="{{ $p['display_name'] }}" width="{{ (int) $s['avatar_size'] }}" height="{{ (int) $s['avatar_size'] }}" fetchpriority="high">
            @else
                <div class="bp-avatar bp-avatar--initials" aria-hidden="true">{{ $initials }}</div>
            @endif

            <h1 class="bp-name">
                {{ $p['display_name'] }}
                @if ($verified)
                    <svg class="bp-verified" viewBox="0 0 24 24" role="img" aria-label="{{ __('profile.verified') }}"><path fill="#1D9BF0" d="M22.5 12.5c0-1.58-.875-2.95-2.148-3.6.154-.435.238-.905.238-1.4 0-2.21-1.71-3.998-3.818-3.998-.47 0-.92.084-1.336.25C14.818 2.415 13.51 1.5 12 1.5s-2.816.917-3.437 2.25c-.415-.165-.866-.25-1.336-.25-2.11 0-3.818 1.79-3.818 4 0 .494.083.964.237 1.4-1.272.65-2.147 2.018-2.147 3.6 0 1.495.782 2.798 1.942 3.486-.02.17-.032.34-.032.514 0 2.21 1.708 4 3.818 4 .47 0 .92-.086 1.335-.25.62 1.334 1.926 2.25 3.437 2.25 1.512 0 2.818-.916 3.437-2.25.415.163.865.248 1.336.248 2.11 0 3.818-1.79 3.818-4 0-.174-.012-.344-.033-.513 1.158-.687 1.943-1.99 1.943-3.484z"/><path fill="#fff" d="m10.39 16.47-3.65-3.65 1.41-1.41 2.24 2.24 5.3-5.3 1.41 1.41z"/></svg>
                @endif
            </h1>

            @if ($p['title'])
                <p class="bp-title">{{ $p['title'] }}</p>
            @endif

            @if ($p['badge'])
                <span class="bp-chip">{{ __('profile.badges.'.$p['badge']) }}</span>
            @endif

            @if ($p['bio_short'])
                <p class="bp-bio">{{ $p['bio_short'] }}</p>
            @endif

            @if ($p['location'])
                <p class="bp-meta"><x-icon name="map-pin" />{{ $p['location'] }}</p>
            @endif
        </header>

        @if ($p['bio_long'])
            <p class="bp-long">{{ $p['bio_long'] }}</p>
        @endif

        <section class="bp-blocks" aria-label="{{ __('profile.links') }}">
            @foreach ($blocks as $block)
                @include('profile.blocks.'.$block['type'], ['b' => $block['view'], 'block' => $block])
            @endforeach
        </section>

        @if (empty($blocks) && $preview)
            <p class="bp-text">{{ __('profile.empty_preview') }}</p>
        @endif

        @if ($showBranding)
            <footer class="bp-foot">
                <a href="{{ config('app.url') }}?ref=profile" rel="noopener"><span class="bp-foot-mark" aria-hidden="true"></span>{{ __('profile.made_with', ['site' => setting('site.name')]) }}</a>
            </footer>
        @endif
    </main>
</body>
</html>
