{{-- Sidebar ERP — Boss + Supervisor --}}
@php
    $pendingClosingsCount     = \App\Models\CashClosing::where('estado', 'pendiente')->count();
    $activeAlertCount         = \App\Models\StockAlert::where('alerta_activa', true)->count();
    $activeSessionsCount      = \App\Models\CashSession::whereIn('status', ['open', 'pending_closing'])->count();
    $pendingReceiptsCount     = \App\Models\InventoryReceipt::where('estado', 'pendiente')->count();
    $pendingTransfersCount    = \App\Models\StockTransfer::where('estado', 'pendiente')->count();
    $pendingCourtesiesCount   = \App\Models\CourtesyTransaction::where('status', 'pendiente')->count();
    $pendingCashMovementsCount = \App\Models\CashMovement::where('status', 'pendiente')->count();

    $invOpen      = request()->routeIs('inventory.*', 'products.*', 'kardex.*', 'transfers.*', 'inventory.bulk-load');
    $comprasOpen  = request()->routeIs('inventory-receipts.*', 'suppliers.*', 'purchases.*', 'purchase-suggestions.*', 'purchase-orders.*');
    $ventasOpen   = request()->routeIs('sales.*', 'pos.*');
    $reportesOpen = request()->routeIs('reports.*');
    $cajaOpen     = request()->routeIs('cash-closings.*', 'cash-sessions.*', 'cash-session.*', 'cash-closing.*');
    $finOpen      = request()->routeIs('finances.*');
    $adminOpen    = request()->routeIs('users.*', 'sedes.*');
    $operOpen     = request()->routeIs('courtesies.*', 'cash-movements.*', 'approvals.*');
@endphp

<aside class="w-60 sidebar-bg flex flex-col shrink-0 overflow-hidden">

    {{-- Logo --}}
    <div class="h-14 flex items-center gap-3 px-5 border-b border-white/[0.07] shrink-0">
        <div class="flex items-center gap-1.5">
            <span class="text-[16px] font-bold tracking-wide text-white">STOCK<span class="text-stock-accent">365</span></span>
        </div>
        @if(auth()->user()->isBoss())
            <span class="ml-auto text-[9px] font-bold tracking-[0.12em] uppercase px-2 py-[3px] rounded-md bg-stock-accent/25 text-stock-accent border border-stock-accent/20">BOSS</span>
        @else
            <span class="ml-auto text-[9px] font-bold tracking-[0.12em] uppercase px-2 py-[3px] rounded-md bg-white/10 text-blue-200 border border-white/10">SUPER</span>
        @endif
    </div>

    {{-- Nav --}}
    <nav class="flex-1 py-3 overflow-y-auto px-2 sidebar-scroll" style="scrollbar-width:thin;">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('dashboard') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/>
            </svg>
            Dashboard
        </a>

        <div class="sidebar-divider"></div>

        {{-- ── OPERACIONES ────────────────────────────────── --}}
        <div x-data="{ open: true }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                           {{ $operOpen ? 'nav-group-active' : 'nav-group-inactive' }}">
                <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <span class="flex-1 text-left">Operaciones</span>
                @php $totalPendingOper = $pendingCourtesiesCount + $pendingCashMovementsCount; @endphp
                @if($totalPendingOper > 0)
                    <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-pink-500 text-white shadow-sm">{{ $totalPendingOper }}</span>
                @endif
                <svg class="w-3 h-3 shrink-0 opacity-35 ml-1 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.09] space-y-0.5">
                <a href="{{ route('courtesies.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('courtesies.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Cortesías
                    </span>
                    @if($pendingCourtesiesCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-pink-500 text-white">{{ $pendingCourtesiesCount }}</span>
                    @endif
                </a>
                <a href="{{ route('cash-movements.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('cash-movements.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Depósitos / Caja
                    </span>
                    @if($pendingCashMovementsCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-amber-500 text-white">{{ $pendingCashMovementsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('approvals.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('approvals.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Aprobaciones
                    </span>
                    @if($totalPendingOper > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-amber-500 text-white">{{ $totalPendingOper }}</span>
                    @endif
                </a>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        {{-- ── INVENTARIO ──────────────────────────────────── --}}
        <div x-data="{ open: {{ $invOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                           {{ $invOpen ? 'nav-group-active' : 'nav-group-inactive' }}">
                <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <span class="flex-1 text-left">Inventario</span>
                @if($activeAlertCount > 0)
                    <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-red-500 text-white shadow-sm">{{ $activeAlertCount }}</span>
                @endif
                <svg class="w-3 h-3 shrink-0 opacity-35 ml-1 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.09] space-y-0.5">
                @foreach([
                    ['products.*',          route('products.index'),     'Productos'],
                    ['inventory.index',     route('inventory.index'),    'Estado Stock'],
                    ['inventory.movements', route('inventory.movements'),'Movimientos'],
                ] as [$match, $href, $label])
                <a href="{{ $href }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs($match) ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    {{ $label }}
                </a>
                @endforeach

                <a href="{{ route('inventory.index') }}#alertas"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium nav-sub-inactive">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Alertas
                    </span>
                    @if($activeAlertCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-red-500 text-white">{{ $activeAlertCount }}</span>
                    @endif
                </a>

                <a href="{{ route('transfers.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('transfers.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Transferencias
                    </span>
                    @if($pendingTransfersCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-purple-500 text-white">{{ $pendingTransfersCount }}</span>
                    @endif
                </a>

                <a href="{{ route('kardex.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('kardex.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Kardex
                </a>

                @if(auth()->user()->isBoss())
                <a href="{{ route('inventory.bulk-load') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('inventory.bulk-load') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Carga Masiva
                </a>
                @endif
            </div>
        </div>

        <div class="sidebar-divider"></div>

        {{-- ── COMPRAS ─────────────────────────────────────── --}}
        <div x-data="{ open: {{ $comprasOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                           {{ $comprasOpen ? 'nav-group-active' : 'nav-group-inactive' }}">
                <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span class="flex-1 text-left">Compras</span>
                @if($pendingReceiptsCount > 0)
                    <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-indigo-500 text-white shadow-sm">{{ $pendingReceiptsCount }}</span>
                @endif
                <svg class="w-3 h-3 shrink-0 opacity-35 ml-1 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.09] space-y-0.5">
                <a href="{{ route('inventory-receipts.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('inventory-receipts.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Recepciones
                    </span>
                    @if($pendingReceiptsCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-indigo-500 text-white">{{ $pendingReceiptsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('purchases.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('purchases.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Historial Compras
                </a>
                <a href="{{ route('suppliers.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('suppliers.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Proveedores
                </a>
                <a href="{{ route('purchase-suggestions.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('purchase-suggestions.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Sugerencias
                </a>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        {{-- ── VENTAS ──────────────────────────────────────── --}}
        <div x-data="{ open: {{ $ventasOpen || $reportesOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                           {{ $ventasOpen || $reportesOpen ? 'nav-group-active' : 'nav-group-inactive' }}">
                <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span class="flex-1 text-left">Ventas</span>
                <svg class="w-3 h-3 shrink-0 opacity-35 ml-1 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.09] space-y-0.5">
                <a href="{{ route('sales.history') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('sales.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Historial
                </a>
                <a href="{{ route('reports.sales') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('reports.sales') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Reporte Ventas
                </a>
                <a href="{{ route('reports.top-products') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('reports.top-products') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Top Productos
                </a>
                <a href="{{ route('reports.comparison') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('reports.comparison') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Comparativo Sedes
                </a>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        {{-- ── CAJA ────────────────────────────────────────── --}}
        <div x-data="{ open: {{ $cajaOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                           {{ $cajaOpen ? 'nav-group-active' : 'nav-group-inactive' }}">
                <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <span class="flex-1 text-left">Caja</span>
                @if($pendingClosingsCount > 0)
                    <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-amber-500 text-white shadow-sm">{{ $pendingClosingsCount }}</span>
                @elseif($activeSessionsCount > 0)
                    <span class="w-[7px] h-[7px] rounded-full bg-emerald-400 shadow-sm mr-1 shrink-0"></span>
                @endif
                <svg class="w-3 h-3 shrink-0 opacity-35 ml-1 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.09] space-y-0.5">
                <a href="{{ route('cash-sessions.index') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('cash-sessions.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Sesiones
                    </span>
                    @if($activeSessionsCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-emerald-500 text-white">{{ $activeSessionsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('cash-closings.pending') }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('cash-closings.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                        Cierres
                    </span>
                    @if($pendingClosingsCount > 0)
                        <span class="text-[9px] font-bold px-1 py-[1px] rounded bg-amber-500 text-white">{{ $pendingClosingsCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        {{-- ── FINANZAS ─────────────────────────────────────── --}}
        <div x-data="{ open: {{ $finOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                           {{ $finOpen ? 'nav-group-active' : 'nav-group-inactive' }}">
                <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="flex-1 text-left">Finanzas</span>
                <svg class="w-3 h-3 shrink-0 opacity-35 ml-1 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.09] space-y-0.5">
                <a href="{{ route('finances.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('finances.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Resumen
                </a>
                <a href="{{ route('reports.profitability') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('reports.profitability') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Rentabilidad
                </a>
                <a href="{{ route('reports.export-sales') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium nav-sub-inactive">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Exportar CSV
                </a>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        {{-- ── AUDITORÍA ────────────────────────────────────── --}}
        <a href="{{ route('activity-logs.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('activity-logs.*') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Auditoría
        </a>

        {{-- ── CONFIGURACIÓN (boss only) ────────────────────── --}}
        @if(auth()->user()->isBoss())
        <div class="sidebar-divider"></div>
        <div x-data="{ open: {{ $adminOpen ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                           {{ $adminOpen ? 'nav-group-active' : 'nav-group-inactive' }}">
                <svg class="w-[14px] h-[14px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="flex-1 text-left">Configuración</span>
                <svg class="w-3 h-3 shrink-0 opacity-35 ml-1 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-5 pl-3 border-l border-white/[0.09] space-y-0.5">
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('users.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                    Usuarios
                </a>
                <a href="{{ route('sedes.index') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-md text-[12px] font-medium
                          {{ request()->routeIs('sedes.*') ? 'nav-sub-active' : 'nav-sub-inactive' }}">
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
            <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center shrink-0 border border-white/10">
                <span class="text-[12px] font-bold text-white leading-none">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[12px] font-semibold text-white/90 truncate leading-none">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-blue-200/45 truncate mt-0.5">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

</aside>
