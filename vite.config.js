import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/store.js',
                'resources/css/pricing.css',
                'resources/js/pricing.js',
            ],
            refresh: true,
        }),
    ],
});
