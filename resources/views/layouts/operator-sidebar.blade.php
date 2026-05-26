{{-- Sidebar OPERADOR --}}
@php
    $activeCashSession = auth()->user()->sede_id
        ? \App\Models\CashSession::activeForUser(auth()->id(), auth()->user()->sede_id)
        : null;
    $hasSede = auth()->user()->sede_id !== null;
    $pendingMyCashMovements = $hasSede ? \App\Models\CashMovement::where('user_id', auth()->id())->where('status', 'pendiente')->count() : 0;
    $pendingMyReceipts      = $hasSede ? \App\Models\InventoryReceipt::where('user_id', auth()->id())->where('estado', 'pendiente')->count() : 0;
    $pendingSedePayments    = $hasSede ? \App\Models\ReceiptPaymentAllocation::where('source_sede_id', auth()->user()->sede_id)->where('status', 'pending')->count() : 0;
@endphp

<aside class="w-56 sidebar-bg flex flex-col shrink-0 overflow-hidden">

    {{-- Logo --}}
    <div class="h-14 flex items-center gap-2.5 px-5 border-b border-white/[0.07] shrink-0">
        <span class="text-[16px] font-bold tracking-wide text-white">STOCK<span class="text-stock-accent">365</span></span>
        <span class="ml-auto text-[9px] font-bold tracking-[0.12em] uppercase px-2 py-[3px] rounded-md bg-white/10 text-blue-200 border border-white/10">OPR</span>
    </div>

    {{-- Sede badge --}}
    <div class="px-5 py-3 border-b border-white/[0.07] shrink-0">
        <p class="text-[9px] font-semibold text-white/35 uppercase tracking-[0.16em] mb-1">Sede activa</p>
        <p class="text-[13px] font-semibold text-white leading-none">{{ auth()->user()->sede?->nombre ?? '—' }}</p>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 py-3 overflow-y-auto space-y-0.5 px-2 sidebar-scroll" style="scrollbar-width:thin;">

        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('dashboard') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/>
            </svg>
            Dashboard
        </a>

        {{-- ── OPERACIONES ────────────────────────────────────── --}}
        <p class="px-3 pt-5 pb-1.5 text-[9px] font-semibold uppercase tracking-[0.16em] text-white/35">Operaciones</p>

        <a href="{{ $activeCashSession ? route('cash-session.status') : route('cash-session.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('cash-session.*') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
            </svg>
            <span class="flex-1">Mi Caja</span>
            @if($activeCashSession)
                <span class="relative flex h-[7px] w-[7px] shrink-0">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                    <span class="relative inline-flex rounded-full h-[7px] w-[7px] bg-emerald-400"></span>
                </span>
            @endif
        </a>

        <a href="{{ route('pos.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('pos.*') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            Nueva Venta
        </a>

        <a href="{{ route('cash-closing.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('cash-closing.*') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Cierre de Caja
        </a>

        <a href="{{ route('sales.history') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('sales.history') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Historial
        </a>

        {{-- Depósitos / Caja --}}
        @if(auth()->user()->isOperator())
        <a href="{{ route('cash-movements.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('cash-movements.*') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="flex-1">Depósitos / Caja</span>
            @if($pendingMyCashMovements > 0)
                <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-amber-500 text-white">{{ $pendingMyCashMovements }}</span>
            @endif
        </a>
        @endif

        {{-- Pagos entre sedes --}}
        @if($hasSede)
        <a href="{{ route('sede-payments.pending') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('sede-payments.*') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <span class="flex-1">Pagos entre sedes</span>
            @if($pendingSedePayments > 0)
                <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-orange-500 text-white">{{ $pendingSedePayments }}</span>
            @endif
        </a>
        @endif

        {{-- ── INVENTARIO ───────────────────────────────────────── --}}
        @can('receipts.create')
        @if($hasSede)
        <p class="px-3 pt-5 pb-1.5 text-[9px] font-semibold uppercase tracking-[0.16em] text-white/35">Inventario</p>
        <a href="{{ route('inventory-receipts.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium
                  {{ request()->routeIs('inventory-receipts.*') ? 'nav-active' : 'nav-inactive' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="flex-1">Recepción Mercancía</span>
            @if($pendingMyReceipts > 0)
                <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-indigo-500 text-white">{{ $pendingMyReceipts }}</span>
            @endif
        </a>
        @endif
        @endcan

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
