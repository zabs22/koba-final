import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Jetstream application (Tailwind CSS v4)
                'resources/css/app.css',
                'resources/js/app.js',
                // Public website (hand-written CSS, no Tailwind)
                'resources/css/site.css',
                'resources/js/site.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});