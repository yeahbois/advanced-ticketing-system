import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import FullReload from 'vite-plugin-full-reload';
 
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        FullReload([
      'resources/views/**', // reload semua file blade di folder views
        ]),
    ],
    server: {
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
        host: true,
        port: 5000,
        allowedHosts: true,
    },
});