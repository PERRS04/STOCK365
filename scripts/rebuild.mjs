#!/usr/bin/env node
/**
 * STOCK365 — Clean Rebuild
 *
 * Ensures zero stale assets: wipes public/build, runs vite build,
 * then validates CSS integrity and reports the result.
 *
 * Usage: npm run rebuild
 */

import { rm, readFile } from 'fs/promises';
import { existsSync } from 'fs';
import { execSync }    from 'child_process';
import { resolve }     from 'path';

const ROOT      = resolve(process.cwd());
const BUILD_DIR = resolve(ROOT, 'public/build');
const MANIFEST  = resolve(BUILD_DIR, 'manifest.json');

// Must match REQUIRED_CSS in vite.config.js
const REQUIRED_CSS = [
    'page-title', 'metric-value', 'metric-label',
    'btn-primary', 'btn-secondary', 'btn-ghost',
    'shadow-card', 'glow-emerald', 'form-label',
    'empty-state', 'animate-status-ring',
];

const hr  = () => console.log('  ' + '─'.repeat(52));
const ok  = (msg) => console.log(`  \x1b[32m✓\x1b[0m  ${msg}`);
const err = (msg) => console.log(`  \x1b[31m✗\x1b[0m  ${msg}`);
const inf = (msg) => console.log(`  \x1b[34m→\x1b[0m  ${msg}`);

async function main() {
    console.log('\n  \x1b[1mSTOCK365 — Clean Rebuild\x1b[0m');
    hr();

    // ── Step 1: Wipe build directory ──────────────────────────────────────
    if (existsSync(BUILD_DIR)) {
        inf('Cleaning public/build...');
        await rm(BUILD_DIR, { recursive: true, force: true });
        ok('public/build removed');
    } else {
        ok('public/build was already clean');
    }

    // ── Step 2: Vite build ────────────────────────────────────────────────
    inf('Running vite build...');
    try {
        execSync('npm run build', { stdio: 'inherit', cwd: ROOT });
    } catch {
        err('vite build failed — see output above');
        process.exit(1);
    }

    // ── Step 3: Validate manifest ─────────────────────────────────────────
    if (!existsSync(MANIFEST)) {
        err('manifest.json not generated — vite build incomplete');
        process.exit(1);
    }

    const manifest = JSON.parse(await readFile(MANIFEST, 'utf8'));
    const cssEntry = manifest['resources/css/app.css'];

    if (!cssEntry) {
        err('CSS entry missing from manifest.json');
        process.exit(1);
    }

    ok(`Manifest OK  →  ${cssEntry.file}`);

    // ── Step 4: CSS integrity ─────────────────────────────────────────────
    const cssPath = resolve(BUILD_DIR, cssEntry.file);
    if (!existsSync(cssPath)) {
        err(`CSS file not found: ${cssEntry.file}`);
        process.exit(1);
    }

    const css     = await readFile(cssPath, 'utf8');
    const missing = REQUIRED_CSS.filter(c => !css.includes(c));

    if (missing.length > 0) {
        err('CSS integrity FAILED — missing classes:');
        missing.forEach(c => console.log(`       · ${c}`));
        console.log('\n  Check resources/css/app.css contains these @layer components definitions.\n');
        process.exit(1);
    }

    ok(`CSS integrity  →  ${REQUIRED_CSS.length}/${REQUIRED_CSS.length} classes verified`);
    ok(`CSS size       →  ${(css.length / 1024).toFixed(1)} KB`);

    // ── Done ──────────────────────────────────────────────────────────────
    hr();
    console.log('  \x1b[32m✓  Rebuild complete\x1b[0m');
    console.log('');
    console.log('  Start server:');
    console.log('    \x1b[36mphp artisan serve --host=127.0.0.1 --port=8000\x1b[0m');
    console.log('');
    console.log('  Or with hot reload (2 terminals):');
    console.log('    \x1b[36mnpm run dev\x1b[0m');
    console.log('    \x1b[36mphp artisan serve --host=127.0.0.1 --port=8000\x1b[0m');
    console.log('');
}

main();
