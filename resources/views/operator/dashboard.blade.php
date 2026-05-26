@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-5">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-[19px] font-bold text-gray-900 leading-none tracking-tight">{{ $sede?->nombre ?? 'Sin sede asignada' }}</h1>
            <p class="text-[12px] text-gray-400 mt-1">{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
        </div>
        <div>
            @if($cashSession)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-semibold text-emerald-700">
                    <span class="relative flex h-1.5 w-1.5 shrink-0">
                        <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                    </span>
                    Caja abierta · {{ $cashSession->duration }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-[11px] font-semibold text-amber-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                    Sin caja abierta
                </span>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         LIVE CASH HERO — Financial command center (dominant, full width)
    ══════════════════════════════════════════════════════════════════ --}}
    @livewire('live-cash-box')

    {{-- ── Main grid: Timeline | Quick Actions ─────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- Cash Timeline (3/5) --}}
        <div class="col-span-1 lg:col-span-3">
            @livewire('cash-timeline')
        </div>

        {{-- Quick Actions + Last Closing (2/5) --}}
        <div class="col-span-1 lg:col-span-2 space-y-5">

            {{-- Quick actions --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-card">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[13px] font-semibold text-gray-800">Acciones rápidas</h2>
                </div>
                <div class="p-4 space-y-2">

                    <a href="{{ route('pos.create') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 rounded-lg bg-stock-primary hover:bg-blue-800 text-white transition-colors group">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold">Nueva venta</p>
                            <p class="text-[11px] text-blue-200/70">Registrar venta en POS</p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-blue-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('cash-closing.create') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 transition-colors group">
                        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold text-gray-800">Cierre de caja</p>
                            <p class="text-[11px] text-gray-400">Registrar cierre del día</p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('cash-session.status') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 transition-colors group">
                        <svg class="w-4 h-4 shrink-0 text-stock-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold text-gray-800">Mi caja</p>
                            <p class="text-[11px] text-gray-400">Ver estado de la sesión</p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('sales.history') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 transition-colors group">
                        <svg class="w-4 h-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold text-gray-800">Historial ventas</p>
                            <p class="text-[11px] text-gray-400">Consultar ventas anteriores</p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    @can('receipts.create')
                    <a href="{{ route('inventory-receipts.create') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 rounded-lg border {{ $pendingReceipts > 0 ? 'border-indigo-200 bg-indigo-50/50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }} text-gray-700 transition-colors group">
                        <svg class="w-4 h-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold text-gray-800">Recepción mercancía</p>
                            <p class="text-[11px] text-gray-400">
                                {{ $pendingReceipts > 0 ? $pendingReceipts.' pendiente'.($pendingReceipts > 1 ? 's' : '').' de aprobación' : 'Registrar pago y factura' }}
                            </p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    @endcan

                </div>
            </div>

            {{-- Last closing --}}
            @if($lastClosing)
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-card">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[13px] font-semibold text-gray-800">Último cierre</h2>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[12px] text-gray-500">Fecha</span>
                        <span class="text-[13px] font-medium text-gray-800">{{ $lastClosing->fecha_cierre->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[12px] text-gray-500">Total sistema</span>
                        <span class="text-[13px] font-semibold text-gray-900 num">{{ formatCurrency($lastClosing->total_sistema) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[12px] text-gray-500">Diferencia</span>
                        <span class="text-[13px] font-semibold num {{ $lastClosing->isBalanced() ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ formatCurrency($lastClosing->diferencia) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                        <span class="text-[12px] text-gray-500">Estado</span>
                        @if($lastClosing->estado === 'aprobado')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-medium text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Aprobado
                            </span>
                        @elseif($lastClosing->estado === 'rechazado')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 border border-red-200 text-[11px] font-medium text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rechazado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-[11px] font-medium text-amber-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Pendiente
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ── Sales table with inline KPIs ───────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-card">

        {{-- Card header: title + KPIs + link --}}
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <div class="flex items-center gap-5">
                <h2 class="text-[13px] font-semibold text-gray-800">Ventas de hoy</h2>
                @if($todaysSales->count() > 0)
                <div class="hidden sm:flex items-center gap-4 pl-4 border-l border-gray-100">
                    <span class="text-[11px] text-gray-400">
                        Total: <span class="font-semibold text-gray-700 num">{{ formatCurrency($totalVentasHoy) }}</span>
                    </span>
                    <span class="text-[11px] text-gray-400">
                        Transacciones: <span class="font-semibold text-gray-700">{{ $todaysSales->count() }}</span>
                    </span>
                    <span class="text-[11px] text-gray-400">
                        Promedio: <span class="font-semibold text-gray-700 num">{{ formatCurrency($ticketPromedio) }}</span>
                    </span>
                    <span class="text-[11px] text-gray-400">
                        Productos: <span class="font-semibold text-gray-700">{{ $productosVendidosHoy }}</span>
                    </span>
                </div>
                @endif
            </div>
            <a href="{{ route('sales.history') }}" class="text-[11px] text-stock-primary hover:underline font-medium shrink-0">Ver historial →</a>
        </div>

        @if($todaysSales->count() > 0)
            <div class="overflow-hidden">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">#</th>
                            <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Hora</th>
                            <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Productos</th>
                            <th class="text-right px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($todaysSales->take(10) as $sale)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-2.5 text-gray-400 font-mono text-[11px]">#{{ $sale->id }}</td>
                                <td class="px-5 py-2.5 text-gray-500">{{ $sale->fecha_venta->format('H:i') }}</td>
                                <td class="px-5 py-2.5 text-gray-600">{{ $sale->items->count() }} {{ Str::plural('producto', $sale->items->count()) }}</td>
                                <td class="px-5 py-2.5 text-right font-semibold text-gray-900 num">{{ formatCurrency($sale->total_sistema) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($todaysSales->count() > 10)
            <div class="px-5 py-3 border-t border-gray-100 text-center">
                <a href="{{ route('sales.history') }}" class="text-[12px] text-stock-primary hover:underline">Ver {{ $todaysSales->count() - 10 }} ventas más</a>
            </div>
            @endif
        @else
            <div class="px-5 py-10 text-center">
                <p class="text-[13px] text-gray-400">No hay ventas registradas hoy</p>
                <a href="{{ route('pos.create') }}" class="mt-2 inline-block text-[12px] text-stock-primary hover:underline font-medium">Registrar primera venta →</a>
            </div>
        @endif
    </div>

    {{-- ── Low stock ────────────────────────────────────────────────────── --}}
    @if($lowStockProducts->count() > 0)
    <div class="bg-white rounded-xl border border-red-200/60 shadow-card">
        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-red-100/60">
            <span class="w-2 h-2 rounded-full bg-red-500"></span>
            <h2 class="text-[13px] font-semibold text-gray-800">Stock bajo</h2>
            <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100 text-[10px] font-bold text-red-600">{{ $lowStockProducts->count() }}</span>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($lowStockProducts as $item)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="text-[13px] font-medium text-gray-800">{{ $item->product->nombre }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $item->product->marca }} · T{{ $item->product->tamaño }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[13px] font-bold text-red-600">{{ $item->cantidad_stock }} uds</p>
                    <p class="text-[11px] text-gray-400">mín {{ $item->product->stock_minimo }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
