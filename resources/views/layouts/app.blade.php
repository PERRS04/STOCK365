<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'STOCK 365') }} — @yield('title')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased bg-[#f0f2f8]">
    <x-local-env-badge />

    {{-- Toast container --}}
    <div
        x-data="toastSystem()"
        @toast.window="addToast($event.detail)"
        class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"
        style="min-width:300px;max-width:420px"
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
                class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-card-lg border text-sm backdrop-blur-sm"
                :class="{
                    'bg-white/95 border-emerald-200 text-emerald-800': t.type === 'success',
                    'bg-white/95 border-red-200 text-red-800':         t.type === 'error',
                    'bg-white/95 border-amber-200 text-amber-800':     t.type === 'warning',
                    'bg-white/95 border-blue-200 text-blue-800':       t.type === 'info',
                }"
            >
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="t.type==='success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    <path x-show="t.type==='error'"   stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    <path x-show="t.type==='warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    <path x-show="t.type==='info'"    stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="flex-1 font-medium text-[13px]" x-text="t.message"></span>
                <button @click="remove(t.id)" class="flex-shrink-0 opacity-40 hover:opacity-80 transition-opacity mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <div class="flex h-screen">

        {{-- Sidebar --}}
        @if(auth()->user()->isAdminLevel())
            @include('layouts.admin-sidebar')
        @else
            @include('layouts.operator-sidebar')
        @endif

        {{-- Main --}}
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">

            {{-- Topbar --}}
            @php
                $topbarUser  = auth()->user();
                $topbarRole  = $topbarUser->role ?? 'operador';
                $topbarSede  = $topbarUser->sede?->nombre;
                $roleBadgeClass = match($topbarRole) {
                    'boss'       => 'bg-[#003594] text-white border-[#002470]',
                    'supervisor' => 'bg-brand-100 text-brand-700 border-brand-200',
                    default      => 'bg-gray-100 text-gray-600 border-gray-200',
                };
                $roleLabel = match($topbarRole) {
                    'boss'       => 'BOSS',
                    'supervisor' => 'SUPER',
                    default      => 'OPR',
                };
            @endphp
            <header class="h-14 bg-white border-b border-gray-200/60 shadow-[0_1px_0_rgba(0,0,0,0.04)] flex items-center justify-between px-6 shrink-0 gap-4">

                {{-- Left: Brand + Page title --}}
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="text-[13px] font-bold text-gray-800 tracking-tight shrink-0">STOCK<span class="text-stock-primary">365</span></span>
                    <svg class="w-3 h-3 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-[13px] text-gray-400 font-medium truncate">@yield('title')</span>
                </div>

                {{-- Center: Sede context (visible on lg+) --}}
                @if($topbarSede)
                <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                    <span class="text-[11px] font-semibold text-gray-600 leading-none">{{ $topbarSede }}</span>
                </div>
                @endif

                {{-- Right: Clock + Role + User + Logout --}}
                <div class="flex items-center gap-2.5 shrink-0">

                    {{-- Live operational clock --}}
                    <span id="topbar-clock"
                          class="hidden xl:block text-[11px] font-mono text-gray-400 tabular-nums bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100 leading-none select-none"></span>

                    <div class="hidden xl:block w-px h-5 bg-gray-100"></div>

                    {{-- Dark mode toggle --}}
                    <button
                        id="dark-toggle"
                        title="Alternar modo oscuro"
                        onclick="window.toggleDarkMode()"
                        class="flex items-center justify-center w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-gray-700 hover:border-gray-300 hover:bg-gray-50 transition-all"
                    >
                        {{-- Sun icon (shown in dark mode) --}}
                        <svg id="icon-sun" class="w-3.5 h-3.5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                        </svg>
                        {{-- Moon icon (shown in light mode) --}}
                        <svg id="icon-moon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>

                    <div class="hidden xl:block w-px h-5 bg-gray-100"></div>

                    {{-- Role badge --}}
                    <span class="hidden sm:inline-flex items-center text-[9px] font-bold tracking-[0.10em] uppercase px-2 py-[3px] rounded-md border {{ $roleBadgeClass }}">
                        {{ $roleLabel }}
                    </span>

                    <div class="w-px h-5 bg-gray-100"></div>

                    {{-- Avatar + name --}}
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-stock-primary flex items-center justify-center shrink-0 ring-2 ring-white">
                            <span class="text-[11px] font-bold text-white leading-none">{{ strtoupper(substr($topbarUser->name, 0, 1)) }}</span>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-[12px] font-semibold text-gray-800 leading-none">{{ $topbarUser->name }}</p>
                        </div>
                    </div>

                    <div class="w-px h-5 bg-gray-100"></div>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                title="Cerrar sesión"
                                class="flex items-center justify-center w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50/70 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>

                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-auto">
                <div class="px-8 py-8">
                    @yield('content')
                </div>
            </main>

        </div>
    </div>

    <script>
    // ── Dark mode engine ─────────────────────────────────────────
    (function() {
        const STORAGE_KEY = 'stock365-dark-mode';
        const saved = localStorage.getItem(STORAGE_KEY);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = saved !== null ? saved === '1' : prefersDark;
        if (isDark) document.documentElement.classList.add('dark');
        syncToggleIcons(isDark);

        function syncToggleIcons(dark) {
            const sun  = document.getElementById('icon-sun');
            const moon = document.getElementById('icon-moon');
            if (!sun || !moon) return;
            sun.classList.toggle('hidden', !dark);
            moon.classList.toggle('hidden', dark);
        }

        window.toggleDarkMode = function() {
            const dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem(STORAGE_KEY, dark ? '1' : '0');
            syncToggleIcons(dark);
        };
    })();

    // Live operational clock
    (function() {
        function tick() {
            const el = document.getElementById('topbar-clock');
            if (el) el.textContent = new Date().toLocaleTimeString('es-CO', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
        }
        tick();
        setInterval(tick, 1000);
    })();

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
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const msg = form.dataset.confirm;
        if (!msg) return;
        e.preventDefault();
        Swal.fire({
            title: '¿Confirmar acción?',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#003594',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            borderRadius: '12px',
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
    </script>

    @livewireScripts
    @stack('scripts')

    @if(session('success'))
    <script>document.addEventListener('DOMContentLoaded', () => window.toast('{{ addslashes(session('success')) }}', 'success'));</script>
    @endif
    @if(session('error'))
    <script>document.addEventListener('DOMContentLoaded', () => window.toast('{{ addslashes(session('error')) }}', 'error'));</script>
    @endif
    @if(session('warning'))
    <script>document.addEventListener('DOMContentLoaded', () => window.toast('{{ addslashes(session('warning')) }}', 'warning'));</script>
    @endif
    @if(session('info'))
    <script>document.addEventListener('DOMContentLoaded', () => window.toast('{{ addslashes(session('info')) }}', 'info'));</script>
    @endif

</body>
</html>
