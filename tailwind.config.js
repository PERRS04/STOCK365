import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // ── STOCK365 Operational Brand Scale ─────────────────
                brand: {
                    50:  '#f0f4ff',
                    100: '#dce5ff',
                    200: '#b8cbff',
                    300: '#85a5ff',
                    400: '#527bff',
                    500: '#2a57e8',
                    600: '#1440cc',
                    700: '#003594',   // ← PRIMARY
                    800: '#002470',
                    900: '#001548',
                    950: '#000d30',
                },

                // ── Legacy aliases (keep for backward compat) ──────────
                'stock-primary': '#003594',
                'stock-accent':  '#FFD100',
                'stock-dark':    '#2D2D2D',
                'stock-light':   '#F5F5F5',

                // ── Operational semantic ───────────────────────────────
                'op-live':     '#10b981',
                'op-warning':  '#f59e0b',
                'op-critical': '#ef4444',
                'op-pending':  '#8b5cf6',
                'op-idle':     '#9ca3af',

                // ── Surface scale (light) ──────────────────────────────
                'surface-app':    '#f0f2f8',
                'surface-card':   '#ffffff',
                'surface-sunken': '#f9fafb',

                // ── Surface scale (dark) ───────────────────────────────
                'dark-bg':     '#090f1a',
                'dark-surface':'#111827',
                'dark-card':   '#161f2e',
                'dark-raised': '#1c2740',
                'dark-border': 'rgba(255,255,255,0.07)',
            },

            boxShadow: {
                'card':    '0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.06)',
                'card-sm': '0 1px 2px rgba(0,0,0,0.04), 0 2px 8px rgba(0,0,0,0.05)',
                'card-lg': '0 2px 8px rgba(0,0,0,0.06), 0 12px 40px rgba(0,0,0,0.09)',
                'card-xl': '0 4px 16px rgba(0,0,0,0.08), 0 24px 64px rgba(0,0,0,0.12)',
                'focus':         '0 0 0 3px rgba(0,53,148,0.14)',
                'focus-dark':    '0 0 0 3px rgba(82,123,255,0.30)',
                'brand':         '0 4px 20px rgba(0,53,148,0.22)',
                'brand-lg':      '0 8px 40px rgba(0,53,148,0.30)',
                'glow-green': '0 0 0 1px rgba(16,185,129,0.20), 0 4px 24px rgba(16,185,129,0.14)',
                'glow-amber': '0 0 0 1px rgba(245,158,11,0.24), 0 4px 24px rgba(245,158,11,0.15)',
                'glow-red':   '0 0 0 1px rgba(239,68,68,0.28), 0 4px 24px rgba(239,68,68,0.18)',
                'glow-brand': '0 0 0 1px rgba(0,53,148,0.22), 0 4px 24px rgba(0,53,148,0.14)',
            },

            borderRadius: {
                'xs':  '4px',
                'sm':  '6px',
                'DEFAULT': '8px',
                'lg':  '10px',
                'xl':  '12px',
                '2xl': '16px',
                '3xl': '20px',
                '4xl': '24px',
            },

            transitionTimingFunction: {
                'ui':     'cubic-bezier(0.4, 0, 0.2, 1)',
                'spring': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
            },

            transitionDuration: {
                'instant': '80ms',
                'fast':    '120ms',
                'base':    '200ms',
                'slow':    '300ms',
                'xl':      '500ms',
            },

            animation: {
                'status-ring':  'status-ring 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'shimmer':      'shimmer 1.6s ease-in-out infinite',
                'badge-pulse':  'badge-pulse 3s ease-in-out infinite',
                'feed-in':      'feed-in 300ms cubic-bezier(0, 0, 0.2, 1) both',
                'float-idle':   'float-idle 3s ease-in-out infinite',
                'pulse-slow':   'badge-pulse 4s ease-in-out infinite',
                'glow-breathe': 'glow-breathe 3s ease-in-out infinite',
                'scan-line':    'scan-line 8s linear infinite',
            },

            keyframes: {
                'status-ring': {
                    '0%':        { transform: 'scale(1)',    opacity: '0.55' },
                    '65%, 100%': { transform: 'scale(1.65)', opacity: '0'    },
                },
                'shimmer': {
                    '0%':   { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition:  '200% 0' },
                },
                'badge-pulse': {
                    '0%, 100%': { opacity: '1'   },
                    '50%':      { opacity: '0.7' },
                },
                'feed-in': {
                    'from': { opacity: '0', transform: 'translateY(-6px)' },
                    'to':   { opacity: '1', transform: 'translateY(0)'    },
                },
                'float-idle': {
                    '0%, 100%': { transform: 'translateY(0)'    },
                    '50%':      { transform: 'translateY(-4px)' },
                },
                'glow-breathe': {
                    '0%, 100%': { opacity: '0.6', transform: 'scale(1)'    },
                    '50%':      { opacity: '1',   transform: 'scale(1.02)' },
                },
                'scan-line': {
                    '0%':   { transform: 'translateY(-100%)' },
                    '100%': { transform: 'translateY(100vh)' },
                },
            },
        },
    },

    safelist: [
        // Design-system primitives — protect from purging
        'btn', 'btn-sm', 'btn-lg',
        'btn-primary', 'btn-secondary', 'btn-ghost',
        'btn-success', 'btn-danger', 'btn-warning', 'btn-accent',
        'input', 'input-sm', 'form-label',
        'metric-value', 'metric-value-sm', 'metric-label',
        'page-title', 'page-subtitle',
        'shadow-card', 'shadow-card-sm', 'shadow-card-lg',
        'glow-emerald', 'glow-amber', 'glow-red', 'glow-brand',
        'empty-state', 'empty-state-icon', 'empty-state-title', 'empty-state-desc',
        'animate-status-ring', 'skeleton',
        'badge', 'badge-success', 'badge-warning', 'badge-danger',
        'badge-info', 'badge-neutral', 'badge-brand', 'badge-live', 'badge-pending',
        // Brand scale — only the combinations actually used in Blade templates
        'bg-brand-50', 'bg-brand-100', 'bg-brand-700', 'bg-brand-800', 'bg-brand-900',
        'text-brand-600', 'text-brand-700', 'text-brand-800',
        'border-brand-100', 'border-brand-200', 'border-brand-700',
    ],

    plugins: [forms, typography],
};
