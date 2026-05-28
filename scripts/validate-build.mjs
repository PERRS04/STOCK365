#!/usr/bin/env node
/**
 * STOCK365 — CSS Build Validator
 *
 * Validates that the current public/build contains all required
 * STOCK365 design system classes. Run standalone or post-build.
 *
 * Usage: npm run validate
 */

import { readFile } from 'fs/promises';
import { existsSync } from 'fs';
import { resolve }    from 'path';

const BUILD_DIR = resolve(process.cwd(), 'public/build');
const MANIFEST  = resolve(BUILD_DIR, 'manifest.json');

const CHECKS = [
    // Page structure
    { cls: 'page-title',    category: 'Page structure' },
    { cls: 'page-subtitle', category: 'Page structure' },

    // Metrics
    { cls: 'metric-value',    category: 'Metrics' },
    { cls: 'metric-value-sm', category: 'Metrics' },
    { cls: 'metric-label',    category: 'Metrics' },

    // Buttons
    { cls: 'btn-primary',   category: 'Buttons' },
    { cls: 'btn-secondary', category: 'Buttons' },
    { cls: 'btn-ghost',     category: 'Buttons' },
    { cls: 'btn-success',   category: 'Buttons' },
    { cls: 'btn-danger',    category: 'Buttons' },
    { cls: 'btn-warning',   category: 'Buttons' },
    { cls: 'btn-sm',        category: 'Buttons' },

    // Forms
    { cls: 'form-label', category: 'Forms' },
    { cls: 'input',      category: 'Forms' },
    { cls: 'input-sm',   category: 'Forms' },

    // Surfaces
    { cls: 'shadow-card',    category: 'Surfaces' },
    { cls: 'shadow-card-sm', category: 'Surfaces' },
    { cls: 'shadow-card-lg', category: 'Surfaces' },

    // Operational glows
    { cls: 'glow-emerald', category: 'Glows' },
    { cls: 'glow-amber',   category: 'Glows' },
    { cls: 'glow-red',     category: 'Glows' },
    { cls: 'glow-brand',   category: 'Glows' },

    // Empty states
    { cls: 'empty-state',      category: 'Empty states' },
    { cls: 'empty-state-icon', category: 'Empty states' },
    { cls: 'empty-state-title', category: 'Empty states' },

    // Animations
    { cls: 'animate-status-ring', category: 'Animations' },
    { cls: 'animate-feed-in',     category: 'Animations' },
    { cls: 'skeleton',            category: 'Animations' },
];

async function main() {
    console.log('\n  \x1b[1mSTOCK365 — CSS Build Validator\x1b[0m');
    console.log('  ' + '─'.repeat(52));

    if (!existsSync(MANIFEST)) {
        console.log('\n  \x1b[31m✗  public/build/manifest.json not found\x1b[0m');
        console.log('  Run: npm run build\n');
        process.exit(1);
    }

    const manifest = JSON.parse(await readFile(MANIFEST, 'utf8'));
    const cssEntry = manifest['resources/css/app.css'];

    if (!cssEntry) {
        console.log('  \x1b[31m✗  No CSS entry in manifest\x1b[0m');
        process.exit(1);
    }

    const cssPath = resolve(BUILD_DIR, cssEntry.file);
    const css = await readFile(cssPath, 'utf8');
    const sizeKb = (css.length / 1024).toFixed(1);

    console.log(`\n  File  : ${cssEntry.file}`);
    console.log(`  Size  : ${sizeKb} KB`);
    console.log('');

    let pass = 0;
    let fail = 0;
    let currentCategory = '';

    for (const { cls, category } of CHECKS) {
        if (category !== currentCategory) {
            currentCategory = category;
            console.log(`  \x1b[2m${category}\x1b[0m`);
        }
        const found = css.includes(cls);
        if (found) {
            console.log(`  \x1b[32m✓\x1b[0m  .${cls}`);
            pass++;
        } else {
            console.log(`  \x1b[31m✗\x1b[0m  .${cls}  \x1b[31m← MISSING\x1b[0m`);
            fail++;
        }
    }

    console.log('');
    console.log('  ' + '─'.repeat(52));

    if (fail === 0) {
        console.log(`  \x1b[32m✓  All ${pass} classes present — build is healthy\x1b[0m`);
    } else {
        console.log(`  \x1b[32m✓ ${pass} found\x1b[0m   \x1b[31m✗ ${fail} missing\x1b[0m`);
        console.log('\n  The compiled CSS is from a stale build.');
        console.log('  Fix: \x1b[36mnpm run rebuild\x1b[0m\n');
        process.exit(1);
    }
    console.log('');
}

main();
