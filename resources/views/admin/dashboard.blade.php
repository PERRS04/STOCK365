@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-5">

{{-- ══════════════════════════════════════════════════════
     COMMAND HEADER
══════════════════════════════════════════════════════ --}}
<div class="flex items-start justify-between gap-4 flex-wrap">
    <div>
        <div class="flex items-center gap-2.5 mb-2">
            @if($isBoss)
            <span class="inline-flex items-center gap-1.5 text-[9px] font-bold tracking-[0.15em] uppercase
                         bg-[#003594] text-white px-3 py-1.5 rounded-full shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-[#FFD100]"></span>
                Boss
            </span>
            @else
            <span class="inline-flex items-center gap-1.5 text-[9px] font-bold tracking-[0.15em] uppercase
                         bg-blue-100 text-blue-700 px-3 py-1.5 rounded-full">
                Supervisor
            </span>
            @endif
            <span class="text-[11px] text-gray-400 font-medium">
                {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
            </span>
        </div>
        <h1 class="text-[26px] font-black text-gray-900 leading-none tracking-tight">
            @if($isBoss) Red Operativa @else Centro de Control @endif
        </h1>
        <p class="text-[12px] text-gray-400 mt-1.5">
            @if($isBoss)
            Monitoreo en tiempo real · todas las sucursales
            @else
            Panel de supervisión operacional
            @endif
        </p>
    </div>

    {{-- Action badges --}}
    <div class="flex items-center gap-2 flex-wrap">
        @can('closings.approve')
        @if($pendingClosings > 0)
        <a href="{{ route('cash-closings.pending') }}"
           class="flex items-center gap-2 text-[12px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/80
                  px-3.5 py-2 rounded-xl hover:bg-amber-100 transition-colors shadow-sm">
            <span class="relative flex h-[6px] w-[6px] shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-amber-400"></span>
                <span class="relative inline-flex rounded-full h-[6px] w-[6px] bg-amber-400"></span>
            </span>
            {{ $pendingClosings }} cierre{{ $pendingClosings !== 1 ? 's' : '' }} pendiente{{ $pendingClosings !== 1 ? 's' : '' }}
        </a>
        @endif
        @endcan
        @if($alertCount > 0)
        <a href="{{ route('inventory.index') }}"
           class="flex items-center gap-2 text-[12px] font-semibold text-red-700 bg-red-50 border border-red-200/80
                  px-3.5 py-2 rounded-xl hover:bg-red-100 transition-colors shadow-sm">
            <span class="relative flex h-[6px] w-[6px] shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400"></span>
                <span class="relative inline-flex rounded-full h-[6px] w-[6px] bg-red-500"></span>
            </span>
            {{ $alertCount }} alerta{{ $alertCount !== 1 ? 's' : '' }} inventario
        </a>
        @endif
        @if($pendingReceiptsCount > 0)
        <a href="{{ route('inventory-receipts.index') }}"
           class="flex items-center gap-2 text-[12px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200/80
                  px-3.5 py-2 rounded-xl hover:bg-indigo-100 transition-colors shadow-sm">
            <span class="w-[6px] h-[6px] rounded-full bg-indigo-400 shrink-0"></span>
            {{ $pendingReceiptsCount }} recepción{{ $pendingReceiptsCount !== 1 ? 'es' : '' }}
        </a>
        @endif
        @if($isBoss && $pendingClosings === 0 && $alertCount === 0)
        <span class="flex items-center gap-2 text-[12px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/60
                     px-3.5 py-2 rounded-xl">
            <span class="relative flex h-[6px] w-[6px] shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                <span class="relative inline-flex rounded-full h-[6px] w-[6px] bg-emerald-500"></span>
            </span>
            Todo operativo
        </span>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     BOSS: EXECUTIVE CONTROL TOWER (15-second command view)
══════════════════════════════════════════════════════ --}}
@if($isBoss)
@livewire('executive-control-tower')
@endif

{{-- ══════════════════════════════════════════════════════
     BOSS: NETWORK MONEY HERO — no wrapper, full dominance
══════════════════════════════════════════════════════ --}}
@if($isBoss)
@livewire('boss-live-overview')
@endif

{{-- ══════════════════════════════════════════════════════
     KPI STRIP — LIVE
══════════════════════════════════════════════════════ --}}
@livewire('live-kpi-strip')

{{-- ══════════════════════════════════════════════════════
     OPERATIONAL COPILOT
══════════════════════════════════════════════════════ --}}
@if($isBoss)
@livewire('operational-copilot')
@endif

{{-- ══════════════════════════════════════════════════════
     INTELLIGENCE FEED
══════════════════════════════════════════════════════ --}}
@if($isBoss)
@livewire('intelligence-feed')
@endif

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
            <div class="h-[220px]">
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
                <span class="text-[10px] font-bold text-red-600 bg-red-50 border border-red-200/80 px-2.5 py-0.5 rounded-full">{{ $alertCount }}</span>
                @else
                <span class="flex items-center gap-1.5 text-[10px] font-semibold text-emerald-600">
                    <span class="w-1 h-1 rounded-full bg-emerald-400"></span>
                    Normal
                </span>
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
                   class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200/80 px-2.5 py-0.5 rounded-full hover:bg-amber-100 transition-colors">
                    {{ $pendingClosings }}
                </a>
                @else
                <span class="flex items-center gap-1.5 text-[10px] font-semibold text-emerald-600">
                    <span class="w-1 h-1 rounded-full bg-emerald-400"></span>
                    Al día
                </span>
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
                        <p class="text-[11px] font-semibold tabular-nums {{ $closing->diferencia < 0 ? 'text-red-500' : 'text-emerald-500' }}">
                            {{ $closing->diferencia > 0 ? '+' : '' }}${{ number_format(abs($closing->diferencia), 2) }}
                        </p>
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
     BOTTOM ROW (3:2) — operational status map + top products + alert center
══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-5 gap-5">

    {{-- Realtime Operational Status Map (3/5) --}}
    <div class="col-span-3">
        @livewire('operational-status-map')
    </div>

    {{-- Right column: top products + alert center (2/5) --}}
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
                    {{ $i === 0 ? 'text-[#FFD100]' : ($i < 3 ? 'text-gray-400' : 'text-gray-300') }}">{{ $i + 1 }}</span>
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

        {{-- Alert Center --}}
        <div class="flex-1 min-h-0">
            @livewire('boss-alert-center')
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
                    g.addColorStop(0, 'rgba(0,53,148,0.10)');
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
