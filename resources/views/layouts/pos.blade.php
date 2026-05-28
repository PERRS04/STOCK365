<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>STOCK365 — POS Terminal</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* ── POS animations ─────────────────────────────── */

        @keyframes cartSlideIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0);    }
        }
        .cart-slide-in { animation: cartSlideIn 180ms cubic-bezier(0, 0, 0.2, 1) both; }

        @keyframes totalPop {
            0%   { transform: scale(1);    }
            45%  { transform: scale(1.04); }
            100% { transform: scale(1);    }
        }
        .total-pop { animation: totalPop 280ms cubic-bezier(0.34, 1.56, 0.64, 1) both; }

        @keyframes checkDraw {
            to { stroke-dashoffset: 0; }
        }
        .check-draw {
            stroke-dasharray: 30;
            stroke-dashoffset: 30;
            animation: checkDraw 350ms ease-out 200ms forwards;
        }

        @keyframes qtyBump {
            0%   { transform: scale(1);   }
            45%  { transform: scale(1.4); }
            100% { transform: scale(1);   }
        }
        .qty-bump { animation: qtyBump 220ms cubic-bezier(0.34, 1.56, 0.64, 1) both; }

        @keyframes cardPress {
            0%, 100% { transform: scale(1);    }
            50%       { transform: scale(0.96); }
        }
        .card-press { animation: cardPress 120ms ease-out; }

        /* Scrollbar minimal */
        .pos-scroll::-webkit-scrollbar { width: 4px; }
        .pos-scroll::-webkit-scrollbar-track { background: transparent; }
        .pos-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.08); border-radius: 99px; }

        /* Marca pills scrollbar hidden */
        .pills-scroll::-webkit-scrollbar { display: none; }
        .pills-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="font-sans antialiased h-screen flex flex-col overflow-hidden bg-[#f0f2f8]">

    <x-local-env-badge />

    {{-- ── POS Topbar ──────────────────────────────────────────── --}}
    @php
        $posUser = auth()->user();
        $posSede = $posUser->sede?->nombre ?? '—';
    @endphp
    <header class="h-10 bg-[#003594] flex items-center px-4 gap-3 shrink-0 z-10 select-none">

        {{-- Brand --}}
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-[12px] font-black text-white tracking-tight leading-none">
                STOCK<span class="text-[#FFD100]">365</span>
            </span>
            <span class="w-px h-3.5 bg-white/20"></span>
            <span class="text-[10px] font-semibold text-white/50 uppercase tracking-widest">POS</span>
        </div>

        {{-- Sede --}}
        <div class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
            <span class="text-[11px] text-white/75 font-medium truncate max-w-[120px]">{{ $posSede }}</span>
        </div>

        <div class="flex-1"></div>

        {{-- Keyboard hints --}}
        <div class="hidden lg:flex items-center gap-4 text-[10px] text-white/35">
            <span><kbd class="bg-white/10 rounded px-1.5 py-0.5 font-mono text-[9px] text-white/55">Ctrl+B</kbd> buscar</span>
            <span><kbd class="bg-white/10 rounded px-1.5 py-0.5 font-mono text-[9px] text-white/55">↵</kbd> añadir</span>
            <span><kbd class="bg-white/10 rounded px-1.5 py-0.5 font-mono text-[9px] text-white/55">F2</kbd> cobrar</span>
            <span><kbd class="bg-white/10 rounded px-1.5 py-0.5 font-mono text-[9px] text-white/55">Esc</kbd> cancelar</span>
        </div>

        <span class="hidden lg:block w-px h-4 bg-white/20"></span>

        {{-- Operator --}}
        <div class="flex items-center gap-1.5">
            <div class="w-5 h-5 rounded-full bg-white/15 border border-white/20 flex items-center justify-center shrink-0">
                <span class="text-[9px] font-bold text-white leading-none">{{ strtoupper(substr($posUser->name, 0, 1)) }}</span>
            </div>
            <span class="hidden sm:block text-[11px] text-white/65 font-medium max-w-[90px] truncate">{{ explode(' ', $posUser->name)[0] }}</span>
        </div>

        <span class="w-px h-4 bg-white/20"></span>

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-1 text-[11px] text-white/45 hover:text-white/80 transition-colors leading-none">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="hidden xl:inline">Dashboard</span>
        </a>
    </header>

    {{-- ── POS Content ──────────────────────────────────────────── --}}
    <main class="flex-1 flex overflow-hidden min-h-0">
        @yield('pos-content')
    </main>

    {{-- ── Toast system ─────────────────────────────────────────── --}}
    <div
        x-data="toastSystem()"
        @toast.window="addToast($event.detail)"
        class="fixed top-12 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"
        style="min-width:280px;max-width:380px"
    >
        <template x-for="t in toasts" :key="t.id">
            <div
                x-show="t.visible"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="pointer-events-auto flex items-start gap-3 px-3.5 py-2.5 rounded-xl shadow-card-lg border text-sm backdrop-blur-sm"
                :class="{
                    'bg-white/95 border-emerald-200 text-emerald-800': t.type === 'success',
                    'bg-white/95 border-red-200   text-red-800':       t.type === 'error',
                    'bg-white/95 border-amber-200 text-amber-800':     t.type === 'warning',
                    'bg-white/95 border-blue-200  text-blue-800':      t.type === 'info',
                }"
            >
                <span class="flex-1 font-medium text-[12px]" x-text="t.message"></span>
                <button @click="remove(t.id)" class="flex-shrink-0 opacity-40 hover:opacity-80 transition-opacity">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <script>
    function toastSystem() {
        return {
            toasts: [],
            addToast({ message, type = 'info' }) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message, type, visible: true });
                setTimeout(() => this.remove(id), 4500);
            },
            remove(id) {
                const t = this.toasts.find(x => x.id === id);
                if (t) t.visible = false;
                setTimeout(() => { this.toasts = this.toasts.filter(x => x.id !== id); }, 200);
            },
        };
    }
    window.toast = function(message, type = 'info') {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
    };
    </script>

    @livewireScripts
    @stack('scripts')

    @if(session('success'))
    <script>document.addEventListener('DOMContentLoaded', () => window.toast('{{ addslashes(session('success')) }}', 'success'));</script>
    @endif
    @if(session('error'))
    <script>document.addEventListener('DOMContentLoaded', () => window.toast('{{ addslashes(session('error')) }}', 'error'));</script>
    @endif
</body>
</html>
