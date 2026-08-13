import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js', 'resources/js/app-admin.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@assets': '/resources/assets',
        },
    },
    server: {
        // Bind all interfaces (fixes Vite binding only to IPv6 [::1] on some
        // Windows/Node setups, which broke the injected dev CSS/JS URLs).
        host: '0.0.0.0',
        // Keep the hot file URL clean & reachable from this machine; without
        // this the dev URLs were written as http://[::1]:5173 and browsers
        // failed to load stylesheets from them.
        origin: 'http://localhost:5173',
        // The app is served from 127.0.0.1:8501, so Vite assets are cross-origin.
        // Reflect the request Origin instead of echoing `origin` above, or
        // browsers block the fonts/scripts (ACAO mismatch → CORS error).
        cors: { origin: true },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
