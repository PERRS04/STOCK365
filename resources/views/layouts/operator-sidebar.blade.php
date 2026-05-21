{{-- Sidebar OPERADOR --}}
<aside class="w-56 bg-stock-primary flex flex-col shrink-0 overflow-hidden">

    {{-- Logo --}}
    <div class="h-12 flex items-center gap-2.5 px-5 border-b border-white/[0.07] shrink-0">
        <span class="text-[15px] font-bold tracking-wide text-white">STOCK<span class="text-stock-accent">365</span></span>
        <span class="ml-auto text-[9px] font-bold tracking-[0.12em] uppercase px-1.5 py-[3px] rounded bg-white/10 text-blue-200">OPR</span>
    </div>

    {{-- Sede badge --}}
    <div class="px-5 py-3 border-b border-white/[0.07] shrink-0">
        <p class="text-[10px] text-blue-200/40 uppercase tracking-widest mb-0.5">Sede activa</p>
        <p class="text-[13px] font-semibold text-white">{{ auth()->user()->sede->nombre }}</p>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 py-3 overflow-y-auto space-y-0.5 px-2">

        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                  {{ request()->routeIs('dashboard') ? 'bg-white/[0.12] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-3a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/>
            </svg>
            Dashboard
        </a>

        <p class="px-3 pt-4 pb-1.5 text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-200/40">Operaciones</p>

        @php
            $activeCashSession = \App\Models\CashSession::activeForUser(auth()->id(), auth()->user()->sede_id);
        @endphp

        <a href="{{ $activeCashSession ? route('cash-session.status') : route('cash-session.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                  {{ request()->routeIs('cash-session.*') ? 'bg-white/[0.12] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
            </svg>
            <span class="flex-1">Mi Caja</span>
            @if($activeCashSession)
                <span class="w-[7px] h-[7px] rounded-full bg-emerald-400 shrink-0"></span>
            @endif
        </a>

        <a href="{{ route('pos.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                  {{ request()->routeIs('pos.*') ? 'bg-white/[0.12] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            Nueva Venta
        </a>

        <a href="{{ route('cash-closing.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                  {{ request()->routeIs('cash-closing.*') ? 'bg-white/[0.12] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Cierre de Caja
        </a>

        <a href="{{ route('sales.history') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                  {{ request()->routeIs('sales.history') ? 'bg-white/[0.12] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Historial
        </a>

        <p class="px-3 pt-4 pb-1.5 text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-200/40">Inventario</p>

        @can('receipts.create')
        @php $pendingMyReceipts = \App\Models\InventoryReceipt::where('user_id', auth()->id())->where('estado', 'pendiente')->count(); @endphp
        <a href="{{ route('inventory-receipts.create') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all
                  {{ request()->routeIs('inventory-receipts.create') ? 'bg-white/[0.12] text-white' : 'text-blue-100/70 hover:bg-white/[0.07] hover:text-white' }}">
            <svg class="w-[15px] h-[15px] shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="flex-1">Recepción Mercancía</span>
            @if($pendingMyReceipts > 0)
                <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-indigo-400/70 text-white">{{ $pendingMyReceipts }}</span>
            @endif
        </a>
        @endcan

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
