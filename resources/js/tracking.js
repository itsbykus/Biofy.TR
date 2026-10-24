// Consent-gated measurement tags (Google Analytics / Ads / Tag Manager, Meta Pixel, TikTok Pixel).
// The page carries only validated IDs (<script type="application/json" id="biofy-tracking">);
// nothing is loaded from Google, Meta or TikTok until the visitor presses "Accept" in the
// consent bar. The choice is remembered per scope (the main site, or one bio page) for 180 days.

const MAX_AGE = 180 * 24 * 60 * 60 * 1000;

function readChoice(key) {
    try {
        const saved = JSON.parse(localStorage.getItem(key) || 'null');

        return saved && Date.now() - saved.at < MAX_AGE ? saved.value : null;
    } catch {
        return null;
    }
}

function saveChoice(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify({ value, at: Date.now() }));
    } catch {
        // private mode: the bar will simply ask again next time
    }
}

function addScript(src) {
    const script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.append(script);
}

function loadGoogle(ids) {
    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() {
        window.dataLayer.push(arguments);
    };

    if (ids.gtm) {
        window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
        addScript('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(ids.gtm));
    }

    const tags = [ids.ga4, ids.google_ads].filter(Boolean);

    if (tags.length) {
        addScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(tags[0]));
        window.gtag('js', new Date());
        tags.forEach((id) => window.gtag('config', id));
    }
}

function loadMeta(id) {
    if (window.fbq) {
        return;
    }

    const fbq = function () {
        fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
    };
    fbq.push = fbq;
    fbq.loaded = true;
    fbq.version = '2.0';
    fbq.queue = [];
    window.fbq = window._fbq = fbq;
    addScript('https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', id);
    fbq('track', 'PageView');
}

function loadTikTok(id) {
    const ttq = (window.ttq = window.ttq || []);
    window.TiktokAnalyticsObject = 'ttq';
    ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie', 'holdConsent', 'revokeConsent', 'grantConsent'];
    ttq.setAndDefer = (target, method) => {
        target[method] = function () {
            target.push([method].concat(Array.prototype.slice.call(arguments, 0)));
        };
    };
    ttq.methods.forEach((method) => ttq.setAndDefer(ttq, method));
    ttq.instance = (name) => {
        const instance = ttq._i[name] || [];
        ttq.methods.forEach((method) => ttq.setAndDefer(instance, method));

        return instance;
    };
    ttq.load = (sdkId, options) => {
        const src = 'https://analytics.tiktok.com/i18n/pixel/events.js';
        ttq._i = ttq._i || {};
        ttq._i[sdkId] = [];
        ttq._i[sdkId]._u = src;
        ttq._t = ttq._t || {};
        ttq._t[sdkId] = Date.now();
        ttq._o = ttq._o || {};
        ttq._o[sdkId] = options || {};
        addScript(src + '?sdkid=' + encodeURIComponent(sdkId) + '&lib=ttq');
    };
    ttq.load(id);
    ttq.page();
}

let loaded = false;

function load(config) {
    if (loaded) {
        return;
    }

    loaded = true;
    const ids = config.ids || {};

    if (ids.ga4 || ids.google_ads || ids.gtm) {
        loadGoogle(ids);
    }

    if (ids.meta) {
        loadMeta(ids.meta);
    }

    if (ids.tiktok) {
        loadTikTok(ids.tiktok);
    }

    // Bio pages: a click on a link button is reported as an event.
    if (config.clicks) {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.bp-btn');

            if (!button) {
                return;
            }

            const label = (button.querySelector('.bp-btn-title')?.textContent || '').trim().slice(0, 100);
            window.gtag?.('event', 'link_click', { link_text: label });
            window.fbq?.('trackCustom', 'LinkClick', { label });
            window.ttq?.track?.('ClickButton', { content_name: label });
        });
    }
}

const configElement = document.getElementById('biofy-tracking');

if (configElement) {
    let config = {};

    try {
        config = JSON.parse(configElement.textContent || '{}');
    } catch {
        config = {};
    }

    const key = 'biofy.consent.' + (config.scope || 'site');
    const bar = document.getElementById('biofy-consent');
    const choice = readChoice(key);

    if (choice === 'granted') {
        load(config);
    } else if (choice === null && bar) {
        bar.hidden = false;
    }

    bar?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-consent]');

        if (!button) {
            return;
        }

        const value = button.dataset.consent === 'granted' ? 'granted' : 'denied';
        saveChoice(key, value);
        bar.hidden = true;

        if (value === 'granted') {
            load(config);
        } else if (loaded) {
            // Withdrawn after loading: a reload starts the page without the tags.
            window.location.reload();
        }
    });

    // "Cookie preferences" links reopen the bar.
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-consent-open]') && bar) {
            event.preventDefault();
            bar.hidden = false;
            bar.querySelector('[data-consent]')?.focus();
        }
    });
}
