import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/images/*',
                    dest: 'images/'
                }
            ]
        }),
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
    ]
});
