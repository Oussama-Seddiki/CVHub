import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'ziggy-js': path.resolve('vendor/tightenco/ziggy/dist/index.js'),
            'ziggy': path.resolve('vendor/tightenco/ziggy/dist'),
        },
    },
    optimizeDeps: {
        include: ['@fortawesome/fontawesome-free/css/all.css'],
    },
    build: {
        commonjsOptions: {
            strictRequires: true,
        },
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'pinia', '@inertiajs/vue3'],
                }
            }
        }
    }
});
