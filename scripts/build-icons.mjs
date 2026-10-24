// Generates resources/icons/{ui,brands}.json from npm packages (dev-time only).
// UI icons: lucide-static (ISC). Brand icons: simple-icons (CC0-1.0).
// Run: node scripts/build-icons.mjs
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const si = require('simple-icons');

const ui = [
    'link', 'share-2', 'heading', 'align-left', 'minus', 'image', 'images', 'film', 'play', 'music', 'mail', 'phone',
    'message-circle', 'map-pin', 'contact', 'circle-help', 'shopping-bag', 'heart', 'calendar', 'folder', 'square',
    'plus', 'pencil', 'trash-2', 'eye', 'eye-off', 'copy', 'grip-vertical', 'chevron-down', 'chevron-up', 'chevron-right',
    'chevron-left', 'arrow-up', 'arrow-down', 'arrow-right', 'external-link', 'check', 'x', 'menu', 'log-out', 'settings',
    'user', 'users', 'shield', 'shield-check', 'key-round', 'chart-column', 'chart-line', 'globe', 'layout-dashboard',
    'palette', 'file-text', 'package', 'server', 'database', 'bell', 'search', 'download', 'upload', 'qr-code',
    'smartphone', 'monitor', 'refresh-cw', 'lock', 'triangle-alert', 'info', 'sparkles', 'zap', 'star', 'badge-check',
    'archive', 'rotate-ccw', 'send', 'clock', 'inbox', 'history', 'sun', 'moon', 'languages', 'layers',
    'sliders-horizontal', 'circle-check', 'circle-x', 'mouse-pointer-click', 'code', 'hard-drive', 'wrench', 'megaphone',
    'scroll-text', 'activity', 'tag', 'gift', 'house', 'book-open', 'save', 'undo-2', 'circle-plus', 'type', 'list',
    'newspaper', 'arrow-left', 'trending-up',
    'briefcase', 'shopping-cart', 'video', 'camera', 'graduation-cap', 'gamepad-2', 'podcast', 'mic', 'coffee',
    'utensils', 'ticket', 'rocket', 'scissors', 'qr-code', 'link-2', 'bar-chart-3', 'target',
];

const brands = {
    instagram: 'siInstagram', x: 'siX', tiktok: 'siTiktok', youtube: 'siYoutube', facebook: 'siFacebook',
    github: 'siGithub', twitch: 'siTwitch', threads: 'siThreads', bluesky: 'siBluesky', telegram: 'siTelegram',
    discord: 'siDiscord', pinterest: 'siPinterest', snapchat: 'siSnapchat', spotify: 'siSpotify', behance: 'siBehance',
    dribbble: 'siDribbble', medium: 'siMedium', reddit: 'siReddit', mastodon: 'siMastodon', whatsapp: 'siWhatsapp',
    soundcloud: 'siSoundcloud', apple_music: 'siApplemusic', deezer: 'siDeezer', vimeo: 'siVimeo',
    buymeacoffee: 'siBuymeacoffee', patreon: 'siPatreon', kofi: 'siKofi', paypal: 'siPaypal',
    github_sponsors: 'siGithubsponsors', calendly: 'siCalendly',
    etsy: 'siEtsy', shopify: 'siShopify', substack: 'siSubstack', kick: 'siKick', google_play: 'siGoogleplay',
    app_store: 'siAppstore', apple: 'siApple', google_maps: 'siGooglemaps', google_drive: 'siGoogledrive',
    notion: 'siNotion', gmail: 'siGmail', signal: 'siSignal', line: 'siLine', viber: 'siViber',
    sahibinden: 'siSahibinden', letterboxd: 'siLetterboxd', goodreads: 'siGoodreads', steam: 'siSteam',
    playstation: 'siPlaystation', udemy: 'siUdemy', coursera: 'siCoursera', zoom: 'siZoom',
    google_meet: 'siGooglemeet', google_calendar: 'siGooglecalendar', linktree: 'siLinktree', bandcamp: 'siBandcamp',
    tidal: 'siTidal', youtube_music: 'siYoutubemusic', wordpress: 'siWordpress', tumblr: 'siTumblr', vk: 'siVk',
    flickr: 'siFlickr', unsplash: 'siUnsplash', figma: 'siFigma', airbnb: 'siAirbnb', tripadvisor: 'siTripadvisor',
    yelp: 'siYelp', gumroad: 'siGumroad', messenger: 'siMessenger', artstation: 'siArtstation',
    deviantart: 'siDeviantart', vsco: 'siVsco', wattpad: 'siWattpad', devto: 'siDevdotto',
    stackoverflow: 'siStackoverflow', gitlab: 'siGitlab', producthunt: 'siProducthunt',
};

const uiOut = {};
const missing = [];

for (const name of ui) {
    try {
        const svg = readFileSync(join(root, 'node_modules/lucide-static/icons', name + '.svg'), 'utf8');
        // Keep only the inner markup; the <svg> wrapper is rendered by the Blade component.
        uiOut[name] = svg.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '').replace(/\s+/g, ' ').trim();
    } catch {
        missing.push(name);
    }
}

const brandOut = {};

for (const [key, exportName] of Object.entries(brands)) {
    const icon = si[exportName];

    if (!icon) {
        missing.push(key);
        continue;
    }

    brandOut[key] = { title: icon.title, hex: '#' + icon.hex, path: icon.path };
}

// LinkedIn is not distributed by simple-icons; a neutral "in" monogram is used instead of the trademark.
brandOut.linkedin = {
    title: 'LinkedIn',
    hex: '#0A66C2',
    path: 'M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z'
        + 'M8.5 5.6a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM7.2 9.6h2.6v8.2H7.2z'
        + 'M11.9 9.6h2.5v1.1c.4-.7 1.2-1.3 2.5-1.3 1.9 0 3.1 1.2 3.1 3.6v4.8h-2.6v-4.3c0-1.2-.4-1.9-1.4-1.9s-1.5.7-1.5 1.9v4.3h-2.6z',
    // Letters are cut out of the square.
    evenodd: true,
};

mkdirSync(join(root, 'resources/icons'), { recursive: true });
writeFileSync(join(root, 'resources/icons/ui.json'), JSON.stringify(uiOut));
writeFileSync(join(root, 'resources/icons/brands.json'), JSON.stringify(brandOut));

console.log(`ui: ${Object.keys(uiOut).length}, brands: ${Object.keys(brandOut).length}`);

if (missing.length) {
    console.error('Missing icons: ' + missing.join(', '));
    process.exitCode = 1;
}
