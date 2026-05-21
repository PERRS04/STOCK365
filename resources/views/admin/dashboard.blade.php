@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

<div>

{{-- ══════════════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════════════ --}}
<div class="flex items-end justify-between mb-6">
    <div>
        <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-gray-400 mb-1.5">
            {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
        </p>
        <h1 class="text-[22px] font-semibold text-gray-900 leading-none">Centro de Control</h1>
    </div>
    <div class="flex items-center gap-2">
        @if($isBoss)
            <span class="text-[10px] font-bold tracking-[0.1em] uppercase bg-stock-primary text-white px-2.5 py-1 rounded-full">BOSS</span>
        @else
            <span class="text-[10px] font-bold tracking-[0.1em] uppercase bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full">SUPERVISOR</span>
        @endif
        @can('closings.approve')
        @if($pendingClosings > 0)
        <a href="{{ route('cash-closings.pending') }}"
           class="flex items-center gap-1.5 text-[12px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/80 px-3 py-1.5 rounded-lg hover:bg-amber-100 transition-colors">
            <span class="w-[6px] h-[6px] rounded-full bg-amber-400 shrink-0"></span>
            {{ $pendingClosings }} cierre{{ $pendingClosings !== 1 ? 's' : '' }} pend.
        </a>
        @endif
        @endcan
        @if($alertCount > 0)
        <a href="{{ route('inventory.index') }}"
           class="flex items-center gap-1.5 text-[12px] font-semibold text-red-700 bg-red-50 border border-red-200/80 px-3 py-1.5 rounded-lg hover:bg-red-100 transition-colors">
            <span class="w-[6px] h-[6px] rounded-full bg-red-400 animate-pulse shrink-0"></span>
            {{ $alertCount }} alerta{{ $alertCount !== 1 ? 's' : '' }}
        </a>
        @endif
        @if($pendingReceiptsCount > 0)
        <a href="{{ route('inventory-receipts.index') }}"
           class="flex items-center gap-1.5 text-[12px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors">
            <span class="w-[6px] h-[6px] rounded-full bg-indigo-400 animate-pulse shrink-0"></span>
            {{ $pendingReceiptsCount }} recepción{{ $pendingReceiptsCount !== 1 ? 'es' : '' }}
        </a>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     KPI STRIP (6 metrics)
══════════════════════════════════════════════════════ --}}
<div class="bg-white border border-gray-200/70 rounded-xl flex divide-x divide-gray-100 mb-5 shadow-[0_1px_4px_rgba(0,0,0,0.05)] overflow-hidden">

    <div class="flex-[1.3] px-6 py-5">
        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">Ventas Hoy</p>
        <p class="text-[26px] font-semibold text-gray-900 leading-none mt-2 tabular-nums">${{ number_format($totalSalesToday, 0, '.', ',') }}</p>
        <p class="text-[12px] text-gray-400 mt-1.5">{{ $transactionCount }} transacciones</p>
    </div>

    <div class="flex-1 px-5 py-5">
        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">Ventas Mes</p>
        <p class="text-[22px] font-semibold text-gray-900 leading-none mt-2 tabular-nums">${{ number_format($totalSalesMonth, 0, '.', ',') }}</p>
        <p class="text-[12px] text-gray-400 mt-1.5">{{ now()->locale('es')->isoFormat('MMMM') }}</p>
    </div>

    @if($isBoss)
    <div class="flex-1 px-5 py-5">
        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">Utilidad</p>
        <p class="text-[22px] font-semibold leading-none mt-2 tabular-nums {{ $utilidadHoy > 0 ? 'text-emerald-600' : 'text-gray-900' }}">${{ number_format($utilidadHoy, 0, '.', ',') }}</p>
        <p class="text-[12px] text-gray-400 mt-1.5">Margen bruto hoy</p>
    </div>
    @endif

    <div class="flex-1 px-5 py-5">
        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">Ticket Prom.</p>
        <p class="text-[22px] font-semibold text-gray-900 leading-none mt-2 tabular-nums">${{ number_format($avgTicket, 2) }}</p>
        <p class="text-[12px] text-gray-400 mt-1.5">Por transacción</p>
    </div>

    <div class="flex-1 px-5 py-5 {{ $pendingClosings > 0 ? 'bg-amber-50/50' : '' }}">
        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">Cierres</p>
        <p class="text-[22px] font-semibold leading-none mt-2 tabular-nums {{ $pendingClosings > 0 ? 'text-amber-500' : 'text-gray-300' }}">{{ $pendingClosings }}</p>
        @if($pendingClosings > 0)
            <a href="{{ route('cash-closings.pending') }}" class="text-[12px] text-amber-600 hover:underline mt-1.5 inline-block font-medium">Revisar →</a>
        @else
            <p class="text-[12px] text-gray-400 mt-1.5">Al día</p>
        @endif
    </div>

    <div class="flex-1 px-5 py-5 {{ $alertCount > 0 ? 'bg-red-50/50' : '' }}">
        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">Alertas</p>
        <p class="text-[22px] font-semibold leading-none mt-2 tabular-nums {{ $alertCount > 0 ? 'text-red-500' : 'text-gray-300' }}">{{ $alertCount }}</p>
        @if($alertCount > 0)
            <a href="{{ route('inventory.index') }}" class="text-[12px] text-red-500 hover:underline mt-1.5 inline-block font-medium">Ver stock →</a>
        @else
            <p class="text-[12px] text-gray-400 mt-1.5">Normal</p>
        @endif
    </div>

    <div class="flex-1 px-5 py-5 {{ $activeSessionsCount > 0 ? 'bg-emerald-50/30' : '' }}">
        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">Cajas Activas</p>
        <p class="text-[22px] font-semibold leading-none mt-2 tabular-nums {{ $activeSessionsCount > 0 ? 'text-emerald-600' : 'text-gray-300' }}">{{ $activeSessionsCount }}</p>
        <a href="{{ route('cash-sessions.index') }}" class="text-[12px] text-gray-400 hover:text-stock-primary mt-1.5 inline-block transition-colors">Ver sesiones →</a>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     MAIN ROW (3:2) — chart + alerts/closings
══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-5 gap-5 mb-5">

    {{-- 7-day chart --}}
    <div class="col-span-3 bg-white border border-gray-200/70 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
        <div class="flex items-center justify-between px-5 pt-5 pb-4">
            <div>
                <h3 class="text-[13px] font-semibold text-gray-800">Tendencia de Ventas</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Últimos 7 días</p>
            </div>
            <a href="{{ route('reports.sales') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Ver reporte →</a>
        </div>
        <div class="px-4 pb-5">
            <div class="h-[220px]">
                <canvas id="chartSemana"></canvas>
            </div>
        </div>
    </div>

    {{-- Critical alerts + pending closings --}}
    <div class="col-span-2 flex flex-col gap-4">

        {{-- Stock crítico (compact) --}}
        <div class="bg-white border border-gray-200/70 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] flex-1 flex flex-col">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 shrink-0">
                <h3 class="text-[13px] font-semibold text-gray-800">Stock Crítico</h3>
                @if($alertCount > 0)
                <span class="text-[10px] font-semibold text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full">{{ $alertCount }}</span>
                @else
                <span class="text-[10px] text-gray-400">Sin alertas</span>
                @endif
            </div>
            <div class="divide-y divide-gray-50 overflow-y-auto flex-1 max-h-[180px]">
                @forelse($stockAlerts->take(6) as $alert)
                @php
                    $pct     = $alert->stock_minimo > 0 ? min(100, round(($alert->stock_actual / $alert->stock_minimo) * 100)) : 0;
                    $danger  = $alert->stock_actual <= 0;
                    $barClr  = $danger ? 'bg-red-500' : ($pct < 40 ? 'bg-orange-400' : 'bg-amber-400');
                @endphp
                <div class="px-5 py-2.5">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[12px] font-medium text-gray-800 truncate flex-1 mr-3">{{ $alert->product?->nombre ?? '—' }}</p>
                        <span class="text-[10px] font-mono font-semibold shrink-0 {{ $danger ? 'text-red-500' : 'text-orange-500' }}">{{ $alert->stock_actual }}/{{ $alert->stock_minimo }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-[3px] bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $barClr }} rounded-full" style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="text-[10px] text-gray-400 w-14 text-right truncate">{{ $alert->sede?->nombre ?? '—' }}</span>
                    </div>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-[12px] text-gray-400">Stock en niveles normales</div>
                @endforelse
            </div>
            @if($alertCount > 6)
            <div class="px-5 py-2.5 border-t border-gray-100 shrink-0">
                <a href="{{ route('inventory.index') }}" class="text-[11px] text-stock-primary hover:underline">Ver {{ $alertCount - 6 }} más →</a>
            </div>
            @endif
        </div>

        {{-- Cierres pendientes (compact) --}}
        @can('closings.approve')
        <div class="bg-white border border-gray-200/70 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-[13px] font-semibold text-gray-800">Cierres Pendientes</h3>
                @if($pendingClosings > 0)
                <a href="{{ route('cash-closings.pending') }}" class="text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full hover:bg-amber-100">{{ $pendingClosings }}</a>
                @endif
            </div>
            @forelse($pendingCashClosings as $closing)
            <div class="flex items-center justify-between px-5 py-2.5 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                <div class="min-w-0">
                    <p class="text-[12px] font-semibold text-gray-800">{{ $closing->sede?->nombre ?? '—' }}</p>
                    <p class="text-[11px] text-gray-400">{{ $closing->user?->name ?? '—' }} · {{ \Carbon\Carbon::parse($closing->fecha_cierre)->format('H:i') }}</p>
                </div>
                <div class="text-right ml-2 shrink-0">
                    <p class="text-[12px] font-semibold text-gray-800">${{ number_format($closing->total_sistema, 0) }}</p>
                    @if($closing->diferencia != 0)
                        <p class="text-[11px] {{ $closing->diferencia < 0 ? 'text-red-500' : 'text-emerald-500' }} font-medium">{{ $closing->diferencia > 0 ? '+' : '' }}${{ number_format(abs($closing->diferencia), 2) }}</p>
                    @else
                        <p class="text-[11px] text-emerald-500">Exacto</p>
                    @endif
                </div>
            </div>
            @empty
            <p class="px-5 py-5 text-center text-[12px] text-gray-400">Sin cierres pendientes</p>
            @endforelse
        </div>
        @endcan

    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     BOTTOM ROW (3:2) — sedes + top products + activity
══════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-5 gap-5">

    {{-- Sede grid (3/5) --}}
    <div class="col-span-3">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-[12px] font-semibold text-gray-500 uppercase tracking-[0.08em]">Resumen por Sede — Hoy</h3>
            @can('users.manage')
            <a href="{{ route('sedes.index') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Gestionar →</a>
            @endcan
        </div>
        <div class="grid grid-cols-2 gap-3">
            @foreach($sedeStats as $s)
            <div class="bg-white border border-gray-200/70 rounded-xl p-4 hover:border-gray-300 hover:shadow-sm transition-all shadow-[0_1px_4px_rgba(0,0,0,0.04)]">
                <div class="flex items-start justify-between mb-3">
                    <h4 class="text-[13px] font-semibold text-gray-800">{{ $s['nombre'] }}</h4>
                    <div class="flex items-center gap-1">
                        @if($s['alertas'] > 0)
                            <span class="text-[9px] font-bold bg-red-50 text-red-500 border border-red-200 px-1 py-[1px] rounded-full">{{ $s['alertas'] }}</span>
                        @endif
                        @if($s['cierres_pend'] > 0)
                            <span class="text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-200 px-1 py-[1px] rounded-full">{{ $s['cierres_pend'] }}c</span>
                        @endif
                        @if($s['alertas'] === 0 && $s['cierres_pend'] === 0)
                            <span class="w-[6px] h-[6px] rounded-full bg-emerald-400"></span>
                        @endif
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-[0.1em] text-gray-400">Ventas</p>
                        <p class="text-[18px] font-semibold text-gray-900 tabular-nums leading-tight">${{ number_format($s['ventas_hoy'], 0) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-semibold uppercase tracking-[0.1em] text-gray-400">Tx</p>
                        <p class="text-[18px] font-semibold text-gray-500 tabular-nums leading-tight">{{ $s['transacciones'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Right column: top products + activity (2/5) --}}
    <div class="col-span-2 flex flex-col gap-4">

        {{-- Top 5 products --}}
        <div class="bg-white border border-gray-200/70 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)]">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-[13px] font-semibold text-gray-800">Top Productos</h3>
                <a href="{{ route('reports.top-products') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Ver más →</a>
            </div>
            @forelse($topProducts as $i => $product)
            <div class="flex items-center gap-3 px-5 py-2.5 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                <span class="text-[11px] font-bold w-4 text-center {{ $i === 0 ? 'text-stock-accent' : 'text-gray-300' }}">{{ $i + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-[12px] font-medium text-gray-800 truncate">{{ $product->nombre }}</p>
                    <p class="text-[10px] text-gray-400">{{ number_format($product->unidades) }} uds.</p>
                </div>
                <p class="text-[12px] font-semibold text-emerald-600 tabular-nums">${{ number_format($product->ingresos, 0) }}</p>
            </div>
            @empty
            <p class="px-5 py-6 text-center text-[12px] text-gray-400">Sin ventas registradas</p>
            @endforelse
        </div>

        {{-- Activity feed (compact) --}}
        <div class="bg-white border border-gray-200/70 rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.05)] flex-1 flex flex-col">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 shrink-0">
                <h3 class="text-[13px] font-semibold text-gray-800">Actividad Reciente</h3>
                <a href="{{ route('activity-logs.index') }}" class="text-[11px] font-medium text-stock-primary hover:underline">Ver todo →</a>
            </div>
            <div class="divide-y divide-gray-50 overflow-y-auto flex-1">
                @forelse($recentActivity as $log)
                @php
                    $type = str_contains($log->action, 'venta')        ? 'emerald'
                          : (str_contains($log->action, 'cierre')       ? 'amber'
                          : (str_contains($log->action, 'caja')         ? 'teal'
                          : (str_contains($log->action, 'inventario') || str_contains($log->action, 'recepcion') ? 'blue' : 'gray')));
                @endphp
                <div class="flex items-start gap-3 px-5 py-3 hover:bg-[#fafbfc] transition-colors">
                    <div class="mt-[5px] w-[6px] h-[6px] rounded-full shrink-0 bg-{{ $type }}-400"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[12px] text-gray-700 leading-snug truncate">{{ $log->description }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $log->user_name }} · {{ $log->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <p class="px-5 py-8 text-center text-[12px] text-gray-400">Sin actividad</p>
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
    const tooltip = {
        backgroundColor: '#111827', titleColor: '#9ca3af', bodyColor: '#f9fafb',
        padding: 10, cornerRadius: 8, displayColors: false,
    };
    const ctxSemana = document.getElementById('chartSemana');
    if (ctxSemana) {
        new Chart(ctxSemana, {
            type: 'line',
            data: {
                labels: semanaData.map(d => d.label),
                datasets: [{
                    data: semanaData.map(d => d.total),
                    borderColor: '#003594',
                    backgroundColor: (ctx) => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height);
                        g.addColorStop(0, 'rgba(0,53,148,0.10)');
                        g.addColorStop(1, 'rgba(0,53,148,0)');
                        return g;
                    },
                    borderWidth: 2, tension: 0.42, fill: true,
                    pointBackgroundColor: '#fff', pointBorderColor: '#003594',
                    pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltip, callbacks: { label: ctx => ' $' + ctx.parsed.y.toLocaleString('en-US') } }
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: '#9ca3af', font: { size: 11, family: 'Inter' } } },
                    y: { grid: { color: 'rgba(0,0,0,0.04)' }, border: { display: false },
                         ticks: { color: '#9ca3af', font: { size: 11, family: 'Inter' }, callback: v => '$' + (v >= 1000 ? (v/1000).toFixed(1)+'k' : v) } }
                }
            }
        });
    }
})();
</script>
@endpush

@endsection
