{{-- Sidebar ERP — Boss + Supervisor --}}
@php
    $pendingClosingsCount  = \App\Models\CashClosing::where('estado', 'pendiente')->count();
    $activeAlertCount      = \App\Models\StockAlert::where('alerta_activa', true)->count();
    $activeSessionsCount   = \App\Models\CashSession::whereIn('status', ['open', 'pending_closing'])->count();
    $pendingReceiptsCount  = \App\Models\InventoryReceipt::where('estado', 'pendiente')->count();
    $pendingTransfersCount = \App\Models\StockTransfer::where('estado', 'pendiente')->count();

    $invOpen      = request()->routeIs('inventory.*', 'products.*', 'kardex.*', 'transfers.*', 'inventory.bulk-load');
    $comprasOpen  = request()->routeIs('inventory-receipts.*', 'suppliers.*', 'purchases.*', 'purchase-suggestions.*', 'purchase-orders.*');
    $ventasOpen   = request()->routeIs('sales.*', 'pos.*');
    $reportesOpen = request()->routeIs('reports.*');
    $cajaOpen     = request()->routeIs('cash-closings.*', 'cash-sessions.*', 'cash-session.*', 'cash-closing.*');
    $finOpen      = request()->routeIs('finances.*');
    $adminOpen    = request()->routeIs('users.*', 'sedes.*');
@endphp

<aside class="w-60 bg-stock-primary flex flex-col shrink-0 overflow-hidden">

    {{-- Logo --}}
    <div class="h-12 flex items-center gap-2.5 px-5 border-b border-white/[0.07] shrink-0">
        <span class="text-[15px] font-bold tracking-wide text-white">STOCK<span class="text-stock-accent">365</span></span>
        @if(auth()->user()->isBoss())
            <span class="ml-auto text-[9px] font-bold tracking-[0.12em] uppercase px-1.5 py-[3px] rounded bg-stock-accent/20 text-stock-accent">BOSS</span>
        @else
            <span class="ml-auto text-[9px] font-bold tracking-[0.12em] uppercase px-1.5 py-[3px] rounded bg-white/10 text-blue-200">SUPER</span>
        @endif
    </div>

    {{-- Nav --}}
    <nav class="flex-1 py-3 overflow-y-auto px-2 space-y-0.5 scrollbar-thin">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                  {{ request()->routeIs('dashboard') ? 'bg-white/[0.12] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
            <svg class="w-[14px] h-[14px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/>
            </svg>
            Dashboard
        </a>

        {{-- ── INVENTARIO ────────────────────────────────────── --}}
        <div x-data="{ open: {{ $invOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                           {{ $invOpen ? 'bg-white/[0.10] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
                <svg class="w-[14px] h-[14px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <span class="flex-1 text-left">Inventario</span>
                @if($activeAlertCount > 0)
                    <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-red-500/80 text-white">{{ $activeAlertCount }}</span>
                @endif
                <svg class="w-3 h-3 shrink-0 transition-transform duration-150 opacity-40 ml-1" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.08] space-y-0.5">
                @foreach([
                    ['inventory.index',    'products.*',          route('products.index'),        'Productos'],
                    ['inventory.index',    'inventory.index',     route('inventory.index'),       'Estado Stock'],
                    ['inventory.movements','inventory.movements', route('inventory.movements'),   'Movimientos'],
                ] as [$check, $match, $href, $label])
                <a href="{{ $href }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs($match) ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    {{ $label }}
                </a>
                @endforeach

                <a href="{{ route('inventory.index') }}#alertas"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all text-blue-100/50 hover:text-white hover:bg-white/[0.05]">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Alertas
                    </span>
                    @if($activeAlertCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-red-500/70 text-white">{{ $activeAlertCount }}</span>
                    @endif
                </a>

                <a href="{{ route('transfers.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('transfers.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Transferencias
                    </span>
                    @if($pendingTransfersCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-purple-400/80 text-white">{{ $pendingTransfersCount }}</span>
                    @endif
                </a>

                <a href="{{ route('kardex.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('kardex.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Kardex
                </a>
                @if(auth()->user()->isBoss())
                <a href="{{ route('inventory.bulk-load') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('inventory.bulk-load') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Carga Masiva
                </a>
                @endif
            </div>
        </div>

        {{-- ── COMPRAS ───────────────────────────────────────── --}}
        <div x-data="{ open: {{ $comprasOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                           {{ $comprasOpen ? 'bg-white/[0.10] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
                <svg class="w-[14px] h-[14px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span class="flex-1 text-left">Compras</span>
                @if($pendingReceiptsCount > 0)
                    <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-indigo-400/80 text-white">{{ $pendingReceiptsCount }}</span>
                @endif
                <svg class="w-3 h-3 shrink-0 transition-transform duration-150 opacity-40 ml-1" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.08] space-y-0.5">
                <a href="{{ route('inventory-receipts.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('inventory-receipts.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Recepciones
                    </span>
                    @if($pendingReceiptsCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-indigo-400/80 text-white">{{ $pendingReceiptsCount }}</span>
                    @endif
                </a>

                <a href="{{ route('purchases.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('purchases.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Historial Compras
                </a>

                <a href="{{ route('suppliers.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('suppliers.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Proveedores
                </a>

                <a href="{{ route('purchase-suggestions.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('purchase-suggestions.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Sugerencias
                </a>
            </div>
        </div>

        {{-- ── VENTAS ────────────────────────────────────────── --}}
        <div x-data="{ open: {{ $ventasOpen || $reportesOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                           {{ $ventasOpen || $reportesOpen ? 'bg-white/[0.10] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
                <svg class="w-[14px] h-[14px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span class="flex-1 text-left">Ventas</span>
                <svg class="w-3 h-3 shrink-0 transition-transform duration-150 opacity-40 ml-1" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.08] space-y-0.5">
                <a href="{{ route('sales.history') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('sales.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Historial
                </a>
                <a href="{{ route('reports.sales') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('reports.sales') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Reporte Ventas
                </a>
                <a href="{{ route('reports.top-products') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('reports.top-products') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Top Productos
                </a>
                <a href="{{ route('reports.comparison') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('reports.comparison') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Comparativo Sedes
                </a>
            </div>
        </div>

        {{-- ── CAJA ──────────────────────────────────────────── --}}
        <div x-data="{ open: {{ $cajaOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                           {{ $cajaOpen ? 'bg-white/[0.10] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
                <svg class="w-[14px] h-[14px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <span class="flex-1 text-left">Caja</span>
                @if($pendingClosingsCount > 0)
                    <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-amber-500/80 text-white">{{ $pendingClosingsCount }}</span>
                @elseif($activeSessionsCount > 0)
                    <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-emerald-500/60 text-white">{{ $activeSessionsCount }}</span>
                @endif
                <svg class="w-3 h-3 shrink-0 transition-transform duration-150 opacity-40 ml-1" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.08] space-y-0.5">
                <a href="{{ route('cash-sessions.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('cash-sessions.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Sesiones
                    </span>
                    @if($activeSessionsCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-emerald-500/70 text-white">{{ $activeSessionsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('cash-closings.pending') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('cash-closings.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Cierres
                    </span>
                    @if($pendingClosingsCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-amber-500/70 text-white">{{ $pendingClosingsCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        {{-- ── FINANZAS ──────────────────────────────────────── --}}
        <div x-data="{ open: {{ $finOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                           {{ $finOpen ? 'bg-white/[0.10] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
                <svg class="w-[14px] h-[14px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="flex-1 text-left">Finanzas</span>
                <svg class="w-3 h-3 shrink-0 transition-transform duration-150 opacity-40 ml-1" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.08] space-y-0.5">
                <a href="{{ route('finances.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('finances.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Resumen
                </a>
                <a href="{{ route('reports.profitability') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('reports.profitability') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Rentabilidad
                </a>
                <a href="{{ route('reports.export-sales') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all text-blue-100/50 hover:text-white hover:bg-white/[0.05]">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Exportar CSV
                </a>
            </div>
        </div>

        {{-- ── AUDITORÍA ─────────────────────────────────────── --}}
        <a href="{{ route('activity-logs.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                  {{ request()->routeIs('activity-logs.*') ? 'bg-white/[0.12] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
            <svg class="w-[14px] h-[14px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Auditoría
        </a>

        {{-- ── CONFIGURACIÓN (boss only) ─────────────────────── --}}
        @if(auth()->user()->isBoss())
        <div x-data="{ open: {{ $adminOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                           {{ $adminOpen ? 'bg-white/[0.10] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
                <svg class="w-[14px] h-[14px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="flex-1 text-left">Configuración</span>
                <svg class="w-3 h-3 shrink-0 transition-transform duration-150 opacity-40 ml-1" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.08] space-y-0.5">
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('users.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Usuarios
                </a>
                <a href="{{ route('sedes.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium transition-all
                          {{ request()->routeIs('sedes.*') ? 'text-white bg-white/[0.08]' : 'text-blue-100/50 hover:text-white hover:bg-white/[0.05]' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Sedes
                </a>
            </div>
        </div>
        @endif

    </nav>

    {{-- User footer --}}
    <div class="border-t border-white/[0.07] px-4 py-3 shrink-0">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-medium text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-blue-200/50 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

</aside>
