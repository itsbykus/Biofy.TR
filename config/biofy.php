<?php

/*
|--------------------------------------------------------------------------
| Biofy.TR platform configuration
|--------------------------------------------------------------------------
| Values here are code-level constants and safe defaults. Anything an
| operator is expected to change at runtime lives in `system_settings`
| and is managed from the superadmin panel (see App\Services\Settings).
*/

return [

    // Bumped on every release; compared with the installed version to detect pending updates.
    'version' => '1.1.0',

    'brand_color' => '#FFCF00',

    'locales' => ['tr', 'en'],

    // Marker written by the installer. Lives outside the web root.
    'install_lock' => storage_path('app/private/installed.lock'),

    /*
    | Ed25519 public key (base64) used to verify the signed MANIFEST of a release
    | package. The matching private key is kept by the release maintainer and is
    | never shipped. Empty = release signature verification unavailable.
    */
    'release_public_key' => env('BIOFY_RELEASE_PUBLIC_KEY', ''),

    /*
    | System paths that can never become a profile slug. The superadmin can add
    | more words from the panel (table `reserved_usernames`) but can't remove these.
    */
    'reserved_slugs' => [
        'admin', 'administrator', 'superadmin', 'root', 'system', 'login', 'logout', 'register', 'signup',
        'signin', 'password', 'forgot-password', 'reset-password', 'verify-email', 'email', 'two-factor',
        'auth', 'oauth', 'api', 'dashboard', 'panel', 'settings', 'account', 'profile', 'profiles', 'pricing',
        'features', 'themes', 'about', 'contact', 'help', 'faq', 'support', 'privacy', 'terms', 'cookies',
        'kvkk', 'legal', 'page', 'pages', 'blog', 'docs', 'install', 'installer', 'update', 'cron', 'health',
        'up', 'status', 'go', 'l', 'media', 'build', 'storage', 'assets', 'static', 'public', 'vendor',
        'sitemap', 'sitemap.xml', 'robots.txt', 'favicon.ico', 'manifest', 'well-known', '.well-known',
        'lang', 'locale', 'biofy', 'www', 'mail', 'ftp', 'cdn', 'app', 'null', 'undefined', 'index',
        'home', 'search', 'explore', 'billing', 'checkout', 'payment', 'subscribe', 'webhook', 'webhooks',
        'security', 'test', 'demo', 'u', 'p', 'qr', 'preview', 'error', 'errors',
        // Application folders: refused by their .htaccess when the package sits in the web root (layout C).
        'bootstrap', 'config', 'database', 'resources', 'routes',
    ],

    'slug' => [
        'min' => 3,
        'max' => 30,
        // Lower-case latin letters, digits, single underscores/hyphens; must start and end alphanumeric.
        'pattern' => '/^(?=.{3,30}$)[a-z0-9](?:[a-z0-9]|[_-](?=[a-z0-9]))*$/',
    ],

    'uploads' => [
        // Hard ceiling regardless of package (bytes).
        'max_file_size' => 5 * 1024 * 1024,
        'max_dimension' => 4000,
        // SVG is deliberately not accepted: it can carry scripts.
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'targets' => [
            'avatar' => ['width' => 512, 'height' => 512, 'crop' => true],
            'banner' => ['width' => 1600, 'height' => 600, 'crop' => true],
            'block' => ['width' => 1200, 'height' => 1200, 'crop' => false],
            'gallery' => ['width' => 1600, 'height' => 1600, 'crop' => false],
            'seo' => ['width' => 1200, 'height' => 630, 'crop' => true],
            'brand' => ['width' => 800, 'height' => 400, 'crop' => false],
            'hero' => ['width' => 1600, 'height' => 1600, 'crop' => false],
            'blog' => ['width' => 1600, 'height' => 900, 'crop' => true],
            'favicon' => ['width' => 512, 'height' => 512, 'crop' => true],
        ],
    ],

    /*
    | Embeds are never taken from user-provided iframe code. Each provider below
    | parses an ID from a URL and the iframe source is rebuilt from that ID.
    */
    'embed_hosts' => [
        'youtube' => ['www.youtube-nocookie.com'],
        'vimeo' => ['player.vimeo.com'],
        'spotify' => ['open.spotify.com'],
        'soundcloud' => ['w.soundcloud.com'],
        'apple_music' => ['embed.music.apple.com'],
        'deezer' => ['widget.deezer.com'],
        'openstreetmap' => ['www.openstreetmap.org'],
    ],

    'social_platforms' => [
        'instagram' => ['hosts' => ['instagram.com'], 'template' => 'https://instagram.com/{handle}'],
        'x' => ['hosts' => ['x.com', 'twitter.com'], 'template' => 'https://x.com/{handle}'],
        'tiktok' => ['hosts' => ['tiktok.com'], 'template' => 'https://www.tiktok.com/@{handle}'],
        'youtube' => ['hosts' => ['youtube.com', 'youtu.be'], 'template' => 'https://www.youtube.com/@{handle}'],
        'facebook' => ['hosts' => ['facebook.com', 'fb.com'], 'template' => 'https://facebook.com/{handle}'],
        'linkedin' => ['hosts' => ['linkedin.com'], 'template' => 'https://www.linkedin.com/in/{handle}'],
        'github' => ['hosts' => ['github.com'], 'template' => 'https://github.com/{handle}'],
        'twitch' => ['hosts' => ['twitch.tv'], 'template' => 'https://twitch.tv/{handle}'],
        'threads' => ['hosts' => ['threads.net', 'threads.com'], 'template' => 'https://www.threads.net/@{handle}'],
        'bluesky' => ['hosts' => ['bsky.app'], 'template' => 'https://bsky.app/profile/{handle}'],
        'whatsapp' => ['hosts' => ['wa.me', 'whatsapp.com'], 'template' => null], // phone number -> https://wa.me/<digits>
        'telegram' => ['hosts' => ['t.me', 'telegram.me'], 'template' => 'https://t.me/{handle}'],
        'discord' => ['hosts' => ['discord.gg', 'discord.com'], 'template' => 'https://discord.gg/{handle}'],
        'pinterest' => ['hosts' => ['pinterest.com'], 'template' => 'https://pinterest.com/{handle}'],
        'snapchat' => ['hosts' => ['snapchat.com'], 'template' => 'https://www.snapchat.com/add/{handle}'],
        'spotify' => ['hosts' => ['spotify.com'], 'template' => null],
        'behance' => ['hosts' => ['behance.net'], 'template' => 'https://www.behance.net/{handle}'],
        'dribbble' => ['hosts' => ['dribbble.com'], 'template' => 'https://dribbble.com/{handle}'],
        'medium' => ['hosts' => ['medium.com'], 'template' => 'https://medium.com/@{handle}'],
        'reddit' => ['hosts' => ['reddit.com'], 'template' => 'https://www.reddit.com/user/{handle}'],
        'mastodon' => ['hosts' => [], 'template' => null],
        'email' => ['hosts' => [], 'template' => null], // e-mail address -> mailto:
        'website' => ['hosts' => [], 'template' => null],
    ],

    'badges' => ['creator', 'business', 'artist', 'developer', 'freelancer', 'musician', 'brand', 'agency'],

    'analytics' => [
        // A repeated view from the same pseudonymous visitor within this window is ignored.
        'view_dedupe_minutes' => 30,
        'click_dedupe_seconds' => 10,
        'bot_pattern' => '/bot|crawl|spider|slurp|facebookexternalhit|embedly|quora link preview|whatsapp|telegrambot|discordbot|preview|headless|lighthouse|curl|wget|python-requests|httpclient|go-http-client|java\/|okhttp|scrapy|phantomjs|selenium|puppeteer|playwright/i',
    ],

    // Bounded "poor man's cron": max work done by a single web-triggered maintenance pass.
    'maintenance' => [
        'batch_size' => 500,
        'web_trigger_interval_minutes' => 15,
    ],
];
