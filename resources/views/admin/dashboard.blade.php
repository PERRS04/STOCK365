@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-5">

{{-- ══════════════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════════════ --}}
<div class="flex items-center justify-between">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 mb-1.5">
            {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
        </p>
        <h1 class="text-[20px] font-bold text-gray-900 leading-none tracking-tight">Centro de Control</h1>
    </div>
    <div class="flex items-center gap-2">
        @if($isBoss)
            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-[0.1em] uppercase bg-stock-primary text-white px-2.5 py-1 rounded-full shadow-sm">
                <span class="w-1 h-1 rounded-full bg-stock-accent"></span>
                Boss
            </span>
        @else
            <span class="text-[10px] font-bold tracking-[0.1em] uppercase bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full">Supervisor</span>
        @endif
        @can('closings.approve')
        @if($pendingClosings > 0)
        <a href="{{ route('cash-closings.pending') }}"
           class="flex items-center gap-1.5 text-[12px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/80 px-3 py-1.5 rounded-lg hover:bg-amber-100 transition-colors">
            <span class="w-[6px] h-[6px] rounded-full bg-amber-400 shrink-0"></span>
            {{ $pendingClosings }} cierre{{ $pendingClosings !== 1 ? 's' : '' }} pendiente{{ $pendingClosings !== 1 ? 's' : '' }}
        </a>
        @endif
        @endcan
        @if($alertCount > 0)
        <a href="{{ route('inventory.index') }}"
           class="flex items-center gap-1.5 text-[12px] font-semibold text-red-700 bg-red-50 border border-red-200/80 px-3 py-1.5 rounded-lg hover:bg-red-100 transition-colors">
            <span class="relative flex h-[7px] w-[7px] shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400"></span>
                <span class="relative inline-flex rounded-full h-[7px] w-[7px] bg-red-500"></span>
            </span>
            {{ $alertCount }} alerta{{ $alertCount !== 1 ? 's' : '' }}
        </a>
        @endif
        @if($pendingReceiptsCount > 0)
        <a href="{{ route('inventory-receipts.index') }}"
           class="flex items-center gap-1.5 text-[12px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors">
            <span class="w-[6px] h-[6px] rounded-full bg-indigo-400 shrink-0"></span>
            {{ $pendingReceiptsCount }} recepción{{ $pendingReceiptsCount !== 1 ? 'es' : '' }}
        </a>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     BOSS: LIVE CASH OVERVIEW
══════════════════════════════════════════════════════ --}}
@if($isBoss)
<div>
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="relative flex h-1.5 w-1.5 shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
            </span>
            <h2 class="section-label">Cajas en Vivo</h2>
        </div>
        <a href="{{ route('cash-sessions.index') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Ver sesiones →</a>
    </div>
    @livewire('boss-live-overview')
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     KPI STRIP — COMMAND CENTER
══════════════════════════════════════════════════════ --}}
<div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">
    {{-- Brand gradient accent line --}}
    <div class="h-[3px] bg-gradient-to-r from-stock-primary via-blue-500 to-indigo-400"></div>

    <div class="grid {{ $isBoss ? 'grid-cols-7' : 'grid-cols-6' }} divide-x divide-gray-100/80">

        {{-- HERO: Ventas Hoy --}}
        <div class="px-6 py-5 relative">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Ventas Hoy</p>
            <p class="text-[28px] font-extrabold text-gray-900 leading-none tabular-nums tracking-tight">${{ number_format($totalSalesToday, 0, '.', ',') }}</p>
            <p class="text-[11px] text-gray-400 mt-2 tabular-nums">{{ $transactionCount }} transacciones</p>
        </div>

        {{-- Ventas Mes --}}
        <div class="px-5 py-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Ventas Mes</p>
            <p class="text-[20px] font-bold text-gray-900 leading-none tabular-nums">${{ number_format($totalSalesMonth, 0, '.', ',') }}</p>
            <p class="text-[11px] text-gray-400 mt-2">{{ now()->locale('es')->isoFormat('MMMM') }}</p>
        </div>

        {{-- Utilidad (boss only) --}}
        @if($isBoss)
        <div class="px-5 py-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Utilidad</p>
            <p class="text-[20px] font-bold leading-none tabular-nums {{ $utilidadHoy > 0 ? 'text-emerald-600' : 'text-gray-700' }}">${{ number_format($utilidadHoy, 0, '.', ',') }}</p>
            <p class="text-[11px] text-gray-400 mt-2">Margen bruto</p>
        </div>
        @endif

        {{-- Ticket Promedio --}}
        <div class="px-5 py-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Ticket Prom.</p>
            <p class="text-[20px] font-bold text-gray-900 leading-none tabular-nums">${{ number_format($avgTicket, 2) }}</p>
            <p class="text-[11px] text-gray-400 mt-2">Por transacción</p>
        </div>

        {{-- Cierres Pendientes --}}
        <div class="px-5 py-5 transition-colors {{ $pendingClosings > 0 ? 'bg-amber-50/60' : '' }}">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] {{ $pendingClosings > 0 ? 'text-amber-500' : 'text-gray-400' }} mb-2.5">Cierres</p>
            <p class="text-[20px] font-bold leading-none tabular-nums {{ $pendingClosings > 0 ? 'text-amber-500' : 'text-gray-300' }}">{{ $pendingClosings }}</p>
            @if($pendingClosings > 0)
                <a href="{{ route('cash-closings.pending') }}" class="text-[11px] text-amber-600 hover:underline mt-2 inline-flex items-center gap-0.5 font-semibold">
                    Revisar
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <p class="text-[11px] text-emerald-500 font-semibold mt-2">Al día</p>
            @endif
        </div>

        {{-- Alertas de Stock --}}
        <div class="px-5 py-5 transition-colors {{ $alertCount > 0 ? 'bg-red-50/40' : '' }}">
            <div class="flex items-center gap-1.5 mb-2.5">
                @if($alertCount > 0)
                <span class="relative flex h-[5px] w-[5px] shrink-0">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400"></span>
                    <span class="relative inline-flex rounded-full h-[5px] w-[5px] bg-red-500"></span>
                </span>
                @endif
                <p class="text-[9px] font-bold uppercase tracking-[0.15em] {{ $alertCount > 0 ? 'text-red-500' : 'text-gray-400' }}">Alertas</p>
            </div>
            <p class="text-[20px] font-bold leading-none tabular-nums {{ $alertCount > 0 ? 'text-red-500' : 'text-gray-300' }}">{{ $alertCount }}</p>
            @if($alertCount > 0)
                <a href="{{ route('inventory.index') }}" class="text-[11px] text-red-500 hover:underline mt-2 inline-flex items-center gap-0.5 font-semibold">
                    Ver stock
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <p class="text-[11px] text-emerald-500 font-semibold mt-2">Normal</p>
            @endif
        </div>

        {{-- Cajas Activas --}}
        <div class="px-5 py-5 {{ $activeSessionsCount > 0 ? 'bg-emerald-50/30' : '' }}">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Cajas</p>
            <div class="flex items-center gap-2">
                @if($activeSessionsCount > 0)
                <span class="relative flex h-[7px] w-[7px] shrink-0">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                    <span class="relative inline-flex rounded-full h-[7px] w-[7px] bg-emerald-500"></span>
                </span>
                @endif
                <p class="text-[20px] font-bold leading-none tabular-nums {{ $activeSessionsCount > 0 ? 'text-emerald-600' : 'text-gray-300' }}">{{ $activeSessionsCount }}</p>
            </div>
            <a href="{{ route('cash-sessions.index') }}" class="text-[11px] text-gray-400 hover:text-stock-primary mt-2 inline-block transition-colors">Ver sesiones →</a>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MAIN ROW (3:2) — sales chart + alerts/closings
══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-5 gap-5">

    {{-- 7-day sales trend --}}
    <div class="col-span-3 bg-white border border-gray-200/60 rounded-2xl shadow-card">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h3 class="text-[13px] font-semibold text-gray-800">Tendencia de Ventas</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Últimos 7 días</p>
            </div>
            <a href="{{ route('reports.sales') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Ver reporte →</a>
        </div>
        <div class="px-6 py-5">
            <div class="h-[200px]">
                <canvas id="chartSemana"></canvas>
            </div>
        </div>
    </div>

    {{-- Critical panel: stock alerts + pending closings --}}
    <div class="col-span-2 flex flex-col gap-4">

        {{-- Stock crítico --}}
        <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card flex-1 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 shrink-0">
                <h3 class="text-[13px] font-semibold text-gray-800">Stock Crítico</h3>
                @if($alertCount > 0)
                <span class="text-[10px] font-bold text-red-600 bg-red-50 border border-red-200/80 px-2 py-0.5 rounded-full">{{ $alertCount }}</span>
                @else
                <span class="text-[10px] font-semibold text-emerald-600">Normal</span>
                @endif
            </div>
            <div class="divide-y divide-gray-50/80 overflow-y-auto flex-1 max-h-[180px]">
                @forelse($stockAlerts->take(6) as $alert)
                @php
                    $pct    = $alert->stock_minimo > 0 ? min(100, round(($alert->stock_actual / $alert->stock_minimo) * 100)) : 0;
                    $danger = $alert->stock_actual <= 0;
                    $barClr = $danger ? 'bg-red-500' : ($pct < 40 ? 'bg-orange-400' : 'bg-amber-400');
                @endphp
                <div class="px-5 py-2.5 hover:bg-gray-50/50 transition-colors">
                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-[12px] font-medium text-gray-800 truncate flex-1 mr-3">{{ $alert->product?->nombre ?? '—' }}</p>
                        <span class="text-[10px] font-mono font-semibold shrink-0 {{ $danger ? 'text-red-500' : 'text-orange-500' }}">{{ $alert->stock_actual }}/{{ $alert->stock_minimo }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-[3px] bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $barClr }} rounded-full transition-all" style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="text-[10px] text-gray-400 w-14 text-right truncate shrink-0">{{ $alert->sede?->nombre ?? '—' }}</span>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <p class="text-[12px] text-gray-400">Todos los niveles normales</p>
                </div>
                @endforelse
            </div>
            @if($alertCount > 6)
            <div class="px-5 py-2.5 border-t border-gray-100 shrink-0">
                <a href="{{ route('inventory.index') }}" class="text-[11px] text-stock-primary hover:underline">Ver {{ $alertCount - 6 }} más →</a>
            </div>
            @endif
        </div>

        {{-- Cierres pendientes --}}
        @can('closings.approve')
        <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-[13px] font-semibold text-gray-800">Cierres Pendientes</h3>
                @if($pendingClosings > 0)
                <a href="{{ route('cash-closings.pending') }}"
                   class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-full hover:bg-amber-100 transition-colors">{{ $pendingClosings }}</a>
                @else
                <span class="text-[10px] font-semibold text-emerald-600">Al día</span>
                @endif
            </div>
            @forelse($pendingCashClosings as $closing)
            <div class="flex items-center justify-between px-5 py-3 {{ !$loop->last ? 'border-b border-gray-50' : '' }} hover:bg-gray-50/50 transition-colors">
                <div class="min-w-0">
                    <p class="text-[12px] font-semibold text-gray-800">{{ $closing->sede?->nombre ?? '—' }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $closing->user?->name ?? '—' }} · {{ \Carbon\Carbon::parse($closing->fecha_cierre)->format('H:i') }}</p>
                </div>
                <div class="text-right ml-3 shrink-0">
                    <p class="text-[13px] font-bold text-gray-800 tabular-nums">${{ number_format($closing->total_sistema, 0) }}</p>
                    @if($closing->diferencia != 0)
                        <p class="text-[11px] font-semibold tabular-nums {{ $closing->diferencia < 0 ? 'text-red-500' : 'text-emerald-500' }}">{{ $closing->diferencia > 0 ? '+' : '' }}${{ number_format(abs($closing->diferencia), 2) }}</p>
                    @else
                        <p class="text-[11px] text-emerald-500 font-semibold">Exacto</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center">
                <p class="text-[12px] text-gray-400">Sin cierres pendientes</p>
            </div>
            @endforelse
        </div>
        @endcan

    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     BOTTOM ROW (3:2) — sede grid + top products + live feed
══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-5 gap-5">

    {{-- Sede performance grid (3/5) --}}
    <div class="col-span-3">
        <div class="flex items-center justify-between mb-3">
            <h2 class="section-label">Rendimiento por Sede · Hoy</h2>
            @can('users.manage')
            <a href="{{ route('sedes.index') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Gestionar →</a>
            @endcan
        </div>
        <div class="grid grid-cols-2 gap-3">
            @foreach($sedeStats as $s)
            @php
                $hasAlerts   = $s['alertas'] > 0;
                $hasPending  = $s['cierres_pend'] > 0;
                $isClean     = !$hasAlerts && !$hasPending;
                $cardBorder  = $hasAlerts ? 'border-red-200/70' : ($hasPending ? 'border-amber-200/70' : 'border-gray-200/60');
                $accentLine  = $hasAlerts
                    ? 'bg-gradient-to-r from-red-400 to-rose-400'
                    : ($hasPending
                        ? 'bg-gradient-to-r from-amber-400 to-orange-400'
                        : 'bg-gradient-to-r from-emerald-400 to-teal-400');
            @endphp
            <div class="bg-white border {{ $cardBorder }} rounded-xl shadow-card overflow-hidden card-lift">
                <div class="h-[2px] {{ $accentLine }}"></div>
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="text-[13px] font-semibold text-gray-800 leading-tight">{{ $s['nombre'] }}</h4>
                        <div class="flex items-center gap-1 shrink-0 ml-2 mt-0.5">
                            @if($hasAlerts)
                                <span class="text-[9px] font-bold bg-red-50 text-red-600 border border-red-200/80 px-1.5 py-[2px] rounded-full">{{ $s['alertas'] }}</span>
                            @endif
                            @if($hasPending)
                                <span class="text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80 px-1.5 py-[2px] rounded-full">{{ $s['cierres_pend'] }}c</span>
                            @endif
                            @if($isClean)
                                <span class="relative flex h-[6px] w-[6px]">
                                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                                    <span class="relative inline-flex rounded-full h-[6px] w-[6px] bg-emerald-400"></span>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1">Ventas</p>
                            <p class="text-[20px] font-bold text-gray-900 tabular-nums leading-none">${{ number_format($s['ventas_hoy'], 0) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1">Tx</p>
                            <p class="text-[20px] font-bold text-gray-500 tabular-nums leading-none">{{ $s['transacciones'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Right column: top products + live ops feed (2/5) --}}
    <div class="col-span-2 flex flex-col gap-4">

        {{-- Top 5 products --}}
        <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-[13px] font-semibold text-gray-800">Top Productos</h3>
                <a href="{{ route('reports.top-products') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Ver más →</a>
            </div>
            @forelse($topProducts as $i => $product)
            <div class="flex items-center gap-3 px-5 py-2.5 {{ !$loop->last ? 'border-b border-gray-50' : '' }} hover:bg-gray-50/50 transition-colors">
                <span class="text-[11px] font-bold w-5 text-center shrink-0
                    {{ $i === 0 ? 'text-stock-accent' : ($i < 3 ? 'text-gray-400' : 'text-gray-300') }}">{{ $i + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-[12px] font-semibold text-gray-800 truncate">{{ $product->nombre }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5 tabular-nums">{{ number_format($product->unidades) }} uds.</p>
                </div>
                <p class="text-[12px] font-bold text-emerald-600 tabular-nums shrink-0">${{ number_format($product->ingresos, 0) }}</p>
            </div>
            @empty
            <div class="px-5 py-8 text-center">
                <p class="text-[12px] text-gray-400">Sin ventas hoy</p>
            </div>
            @endforelse
        </div>

        {{-- ── LIVE OPERATIONAL FEED ──────────────────────────────
             Bloomberg-style priority feed replacing plain activity log
        ──────────────────────────────────────────────────────── --}}
        <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card flex-1 flex flex-col overflow-hidden">

            {{-- Feed header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-1.5 w-1.5 shrink-0">
                        <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                    </span>
                    <h3 class="text-[13px] font-semibold text-gray-800">Feed Operacional</h3>
                </div>
                <a href="{{ route('activity-logs.index') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Ver todo →</a>
            </div>

            {{-- Feed entries --}}
            <div class="overflow-y-auto flex-1 divide-y divide-gray-50">
                @forelse($recentActivity as $log)
                @php
                    $action = strtolower($log->action ?? '');

                    // Priority detection
                    $isHighPriority = str_contains($action, 'alerta')   ||
                                      str_contains($action, 'critico')  ||
                                      str_contains($action, 'faltante');
                    $isMedPriority  = str_contains($action, 'cierre')   ||
                                      str_contains($action, 'recepcion')||
                                      str_contains($action, 'inventario')||
                                      str_contains($action, 'aprobacion');
                    $priority = $isHighPriority ? 'high' : ($isMedPriority ? 'medium' : 'low');

                    // Action category
                    $isVenta     = str_contains($action, 'venta');
                    $isCierre    = str_contains($action, 'cierre');
                    $isCaja      = str_contains($action, 'caja');
                    $isInventory = str_contains($action, 'inventario') || str_contains($action, 'recepcion');
                    $isAlert     = str_contains($action, 'alerta');

                    // Icon scheme per category
                    if ($isAlert) {
                        $iconBg   = 'bg-red-50';
                        $iconClr  = 'text-red-500';
                        $iconPath = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
                    } elseif ($isVenta) {
                        $iconBg   = 'bg-emerald-50';
                        $iconClr  = 'text-emerald-600';
                        $iconPath = 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z';
                    } elseif ($isCierre) {
                        $iconBg   = 'bg-amber-50';
                        $iconClr  = 'text-amber-600';
                        $iconPath = 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
                    } elseif ($isInventory) {
                        $iconBg   = 'bg-indigo-50';
                        $iconClr  = 'text-indigo-600';
                        $iconPath = 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4';
                    } elseif ($isCaja) {
                        $iconBg   = 'bg-blue-50';
                        $iconClr  = 'text-blue-600';
                        $iconPath = 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z';
                    } else {
                        $iconBg   = 'bg-gray-100';
                        $iconClr  = 'text-gray-400';
                        $iconPath = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                    }

                    // Priority left bar color
                    $priorityBar = match($priority) {
                        'high'   => 'bg-red-500',
                        'medium' => 'bg-amber-400',
                        default  => 'bg-transparent',
                    };
                @endphp
                <div class="flex items-stretch hover:bg-gray-50/60 transition-colors animate-feed-in">
                    {{-- Priority bar (3px left accent) --}}
                    <div class="w-[3px] shrink-0 {{ $priorityBar }}"></div>

                    {{-- Content --}}
                    <div class="flex items-start gap-3 px-4 py-3 flex-1 min-w-0">
                        {{-- Semantic icon circle --}}
                        <div class="w-7 h-7 rounded-lg {{ $iconBg }} {{ $iconClr }} flex items-center justify-center shrink-0 mt-[1px]">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                            </svg>
                        </div>

                        {{-- Description + meta --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] text-gray-700 leading-snug font-medium">{{ $log->description }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 tabular-nums">
                                {{ $log->user_name }}
                                <span class="text-gray-300 mx-0.5">·</span>
                                {{ $log->created_at->diffForHumans() }}
                            </p>
                        </div>

                        {{-- Priority badge (only high/medium) --}}
                        @if($priority === 'high')
                            <span class="self-start mt-1 text-[8px] font-bold uppercase tracking-[0.1em] bg-red-100 text-red-600 px-1.5 py-[2px] rounded-md shrink-0 leading-none">HIGH</span>
                        @elseif($priority === 'medium')
                            <span class="self-start mt-1 text-[8px] font-bold uppercase tracking-[0.1em] bg-amber-50 text-amber-600 border border-amber-200/60 px-1.5 py-[2px] rounded-md shrink-0 leading-none">MED</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center px-5 py-10 text-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-[12px] text-gray-400 font-medium">Sin actividad reciente</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

</div>

@push('scripts')
<script>
(function () {
    const semanaData = @json($chartSemana);
    const ctx = document.getElementById('chartSemana');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: semanaData.map(d => d.label),
            datasets: [{
                data: semanaData.map(d => d.total),
                borderColor: '#003594',
                backgroundColor: (c) => {
                    const g = c.chart.ctx.createLinearGradient(0, 0, 0, c.chart.height);
                    g.addColorStop(0, 'rgba(0,53,148,0.09)');
                    g.addColorStop(1, 'rgba(0,53,148,0)');
                    return g;
                },
                borderWidth: 1.5,
                tension: 0.44,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#003594',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    titleColor: '#6b7280',
                    bodyColor: '#f9fafb',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: { label: c => ' $' + c.parsed.y.toLocaleString('en-US') }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#9ca3af', font: { size: 11, family: 'Inter' } }
                },
                y: {
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    border: { display: false },
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 11, family: 'Inter' },
                        callback: v => '$' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v)
                    }
                }
            }
        }
    });
})();
</script>
@endpush

@endsection
