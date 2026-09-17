import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

function copyStablePublicCss() {
    return {
        name: 'copy-stable-public-css',
        closeBundle() {
            const assetsDir = path.resolve('public/build/assets');
            if (!fs.existsSync(assetsDir)) {
                return;
            }

            const cssFiles = fs.readdirSync(assetsDir).filter((file) => /^app-.*\.css$/.test(file));
            if (cssFiles.length === 0) {
                return;
            }

            cssFiles.sort();
            const source = path.join(assetsDir, cssFiles[cssFiles.length - 1]);
            const targetDir = path.resolve('public/css');
            fs.mkdirSync(targetDir, { recursive: true });
            fs.copyFileSync(source, path.join(targetDir, 'app.css'));
        },
    };
}

export default defineConfig({
    plugins: [
        copyStablePublicCss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/heat-results.js',
                'resources/js/public.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
