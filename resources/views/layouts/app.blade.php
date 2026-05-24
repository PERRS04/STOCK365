<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'STOCK 365') }} — @yield('title')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</head>
<body class="font-sans antialiased bg-[#f4f6fa]">

    {{-- Toast Container --}}
    <div
        x-data="toastSystem()"
        @toast.window="addToast($event.detail)"
        class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"
        style="min-width:300px;max-width:400px"
    >
        <template x-for="t in toasts" :key="t.id">
            <div
                x-show="t.visible"
                x-transition:enter="transition ease-out duration-250"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg border text-sm"
                :class="{
                    'bg-white border-emerald-200 text-emerald-800': t.type === 'success',
                    'bg-white border-red-200 text-red-800':         t.type === 'error',
                    'bg-white border-amber-200 text-amber-800':     t.type === 'warning',
                    'bg-white border-blue-200 text-blue-800':       t.type === 'info',
                }"
            >
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="t.type==='success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    <path x-show="t.type==='error'"   stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    <path x-show="t.type==='warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    <path x-show="t.type==='info'"    stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="flex-1 font-medium text-[13px]" x-text="t.message"></span>
                <button @click="remove(t.id)" class="flex-shrink-0 opacity-40 hover:opacity-80 transition-opacity">
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
            <header class="h-12 bg-white border-b border-gray-200/80 flex items-center justify-between px-6 shrink-0">
                <div class="flex items-center gap-2 text-[13px] text-gray-400">
                    <span class="font-medium text-gray-700">{{ config('app.name', 'STOCK 365') }}</span>
                    <span class="text-gray-300">/</span>
                    <span>@yield('title')</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-stock-primary/10 text-stock-primary flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-[13px] text-gray-700 font-medium">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 text-[12px] font-medium hover:bg-red-50 hover:border-red-300 transition-colors">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-auto">
                <div class="px-8 py-7">
                    @yield('content')
                </div>
            </main>

        </div>
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
