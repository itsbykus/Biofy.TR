import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const fonts = ['inter', 'dm-sans', 'space-grotesk', 'outfit', 'playfair', 'fraunces', 'jetbrains-mono'];

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/editor.js',
                'resources/css/profile.css',
                'resources/js/profile.js',
                // One stylesheet per profile font so a bio page only downloads what it uses.
                ...fonts.map((f) => `resources/css/fonts/${f}.css`),
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Production assets are committed into the FTP package; keep them small and predictable.
        cssMinify: true,
        sourcemap: false,
        assetsInlineLimit: 0,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
