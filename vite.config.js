import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],

    server: {
        host: true,      // слушаем 0.0.0.0
        port: 5173,
        strictPort: true,

        hmr: {
            host: '10.58.6.117', // ТВОЙ IP из сети
            port: 5173,
        },
    },
});