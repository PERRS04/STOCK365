import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readFileSync, existsSync } from 'fs';
import { resolve } from 'path';

// ─── STOCK365 Design System — required classes ────────────────────────────────
// If ANY of these are absent from the compiled CSS the build exits non-zero.
// Add new design-system classes here as the system grows.
const REQUIRED_CSS = [
    'page-title', 'page-subtitle',
    'metric-value', 'metric-label',
    'btn-primary', 'btn-secondary', 'btn-ghost', 'btn-success', 'btn-danger',
    'shadow-card', 'glow-emerald', 'glow-amber', 'glow-red',
    'form-label', 'empty-state', 'animate-status-ring', 'skeleton',
];

function stock365IntegrityPlugin() {
    return {
        name: 'stock365-css-integrity',
        enforce: 'post',

        // Fires only during `vite build`, not `vite dev`
        closeBundle() {
            const manifestPath = resolve('public/build/manifest.json');
            if (!existsSync(manifestPath)) return;

            const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
            const cssEntry = manifest['resources/css/app.css'];
            if (!cssEntry) {
                console.error('\n[stock365] ❌  CSS entry missing from manifest\n');
                process.exit(1);
            }

            const cssPath = resolve('public/build', cssEntry.file);
            if (!existsSync(cssPath)) {
                console.error(`\n[stock365] ❌  Built CSS not found: ${cssEntry.file}\n`);
                process.exit(1);
            }

            const css     = readFileSync(cssPath, 'utf8');
            const missing = REQUIRED_CSS.filter(cls => !css.includes(cls));

            if (missing.length > 0) {
                console.error('\n[stock365] ❌  CSS INTEGRITY FAILURE — missing design-system classes:');
                missing.forEach(c => console.error(`[stock365]      · ${c}`));
                console.error('[stock365]    Check resources/css/app.css and run: npm run rebuild\n');
                process.exit(1);
            }

            const kb = (css.length / 1024).toFixed(1);
            console.log(`\n[stock365] ✓  CSS integrity — ${REQUIRED_CSS.length} design-system classes OK`);
            console.log(`[stock365] ✓  ${cssEntry.file} · ${kb} KB\n`);
        },
    };
}

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/app.css',
            'resources/js/app.js',
        ]),
        stock365IntegrityPlugin(),
    ],

    build: {
        // Always wipe public/build before rebuild — the #1 cause of stale assets
        emptyOutDir: true,
    },
});
