@extends('layouts.app')
@section('title', 'Finanzas')
@section('content')

<div class="space-y-5">

    {{-- Header + month selector --}}
    <div class="flex items-center justify-between">
        <h1 class="page-title">Finanzas</h1>
        <form method="GET" class="flex items-center gap-2">
            <select name="mes" class="input input-sm">
                @foreach($meses as $num => $nombre)
                <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ ucfirst($nombre) }}</option>
                @endforeach
            </select>
            <select name="anio" class="input input-sm">
                @foreach($anios as $y)
                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Ver</button>
        </form>
    </div>

    {{-- KPI strip --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card p-5">
            <p class="metric-label">Ventas del Mes</p>
            <p class="metric-value-sm num">${{ number_format($ventasMes, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card p-5">
            <p class="metric-label">Compras del Mes</p>
            <p class="metric-value-sm num">${{ number_format($comprasMes, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-emerald-200/80 shadow-card p-5">
            <p class="metric-label">Utilidad Bruta</p>
            <p class="metric-value-sm num {{ $utilidadBruta >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                ${{ number_format($utilidadBruta, 2) }}
            </p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card p-5">
            <p class="metric-label">Margen</p>
            <p class="metric-value-sm num {{ $margenPct >= 20 ? 'text-emerald-600' : ($margenPct >= 10 ? 'text-amber-600' : 'text-red-600') }}">
                {{ $margenPct }}%
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- Trend chart (6 months) --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-200/60 shadow-card p-5">
            <h2 class="text-[13px] font-semibold text-gray-900 mb-4">Tendencia 6 meses</h2>
            <div class="space-y-3">
                @foreach($trend as $t)
                @php
                    $maxVal = $trend->max('ventas');
                    $ventasPct = $maxVal > 0 ? round(($t['ventas'] / $maxVal) * 100) : 0;
                    $comprasPct = $maxVal > 0 ? round(($t['compras'] / $maxVal) * 100) : 0;
                    $utilPct = $maxVal > 0 ? round((max(0,$t['utilidad']) / $maxVal) * 100) : 0;
                @endphp
                <div class="grid grid-cols-[90px,1fr,80px] gap-3 items-center text-[12px]">
                    <span class="text-gray-500 text-[11px]">{{ $t['label'] }}</span>
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5">
                            <div class="h-1.5 rounded-full bg-stock-primary/80" style="width: {{ $ventasPct }}%; min-width: {{ $t['ventas'] > 0 ? '4px' : '0' }}"></div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="h-1.5 rounded-full bg-amber-400" style="width: {{ $comprasPct }}%; min-width: {{ $t['compras'] > 0 ? '4px' : '0' }}"></div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="h-1.5 rounded-full bg-emerald-400" style="width: {{ $utilPct }}%; min-width: {{ $t['utilidad'] > 0 ? '4px' : '0' }}"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold text-gray-900">${{ number_format($t['utilidad']) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex gap-4 text-[11px] text-gray-500">
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded bg-stock-primary/80"></span>Ventas</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded bg-amber-400"></span>Compras</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded bg-emerald-400"></span>Utilidad</span>
            </div>
        </div>

        {{-- Right column --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Ventas por sede --}}
            <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card p-5">
                <h2 class="text-[13px] font-semibold text-gray-900 mb-3">Ventas por Sede</h2>
                @if($ventasPorSede->isEmpty())
                    <p class="text-[12px] text-gray-400">Sin ventas este mes</p>
                @else
                @php $totalVentas = $ventasPorSede->sum('total'); @endphp
                <div class="space-y-2.5">
                    @foreach($ventasPorSede as $sv)
                    @php $pct = $totalVentas > 0 ? round(($sv->total / $totalVentas) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-[12px] mb-1">
                            <span class="text-gray-700 font-medium">{{ $sv->sede?->nombre ?? 'Desconocida' }}</span>
                            <span class="text-gray-900 font-semibold">${{ number_format($sv->total, 0) }}</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-stock-primary/70 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Compras por proveedor --}}
            <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card p-5">
                <h2 class="text-[13px] font-semibold text-gray-900 mb-3">Compras por Proveedor</h2>
                @if($comprasPorProveedor->isEmpty())
                    <p class="text-[12px] text-gray-400">Sin compras este mes</p>
                @else
                <div class="space-y-2">
                    @foreach($comprasPorProveedor as $cp)
                    <div class="flex items-center justify-between text-[12px]">
                        <span class="text-gray-700 truncate max-w-[130px]">
                            {{ $cp->provider?->nombre ?? 'Sin proveedor' }}
                        </span>
                        <span class="font-semibold text-gray-900">${{ number_format($cp->total, 0) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>

    </div>

    {{-- Cost breakdown --}}
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card p-5">
        <h2 class="text-[13px] font-semibold text-gray-900 mb-4">Desglose de Resultados</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-[13px]">
            <div>
                <p class="text-gray-400 text-[11px] uppercase tracking-widest mb-1">Ingresos por Ventas</p>
                <p class="text-[18px] font-bold text-gray-900">+${{ number_format($ventasMes, 2) }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-[11px] uppercase tracking-widest mb-1">Costo de Mercancía Vendida</p>
                <p class="text-[18px] font-bold text-red-600">-${{ number_format($costoVentasMes, 2) }}</p>
            </div>
            <div class="border-l border-gray-100 pl-8">
                <p class="text-gray-400 text-[11px] uppercase tracking-widest mb-1">Utilidad Bruta</p>
                <p class="text-[18px] font-bold {{ $utilidadBruta >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $utilidadBruta >= 0 ? '+' : '' }}${{ number_format($utilidadBruta, 2) }}
                </p>
            </div>
            <div>
                <p class="text-gray-400 text-[11px] uppercase tracking-widest mb-1">Compras Realizadas</p>
                <p class="text-[18px] font-bold text-amber-600">-${{ number_format($comprasMes, 2) }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Inventario aprovisionado</p>
            </div>
        </div>
    </div>

</div>
@endsection
