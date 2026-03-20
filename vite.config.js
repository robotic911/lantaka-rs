import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readdirSync, statSync } from 'fs';
import { join } from 'path';

function collectFiles(dir, ext) {
    const results = [];
    for (const entry of readdirSync(dir)) {
        const full = join(dir, entry);
        if (statSync(full).isDirectory()) {
            results.push(...collectFiles(full, ext));
        } else if (entry.endsWith(ext)) {
            results.push(full.replace(/\\/g, '/'));
        }
    }
    return results;
}

const input = [
    ...collectFiles('resources/css', '.css'),
    ...collectFiles('resources/js', '.js'),
];

export default defineConfig({
    plugins: [
        laravel({
            input,
            refresh: true,
        }),
    ],
});