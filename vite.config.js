import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        outDir: 'dist',        // 👈 package build output
        emptyOutDir: true,
        rollupOptions: {
            output: {
                entryFileNames: '[name].js',       // stable names
                chunkFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
});
