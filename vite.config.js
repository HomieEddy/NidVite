import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/echo.js',
                'resources/js/reverb-listener.js',
                'resources/js/recaptcha-report.js',
                'resources/js/admin-map-filters.js',
            ],
            refresh: true,
        }),
    ],
});
