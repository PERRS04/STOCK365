@extends('layouts.app')
@section('title', 'Comparativo por Sede')
@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-[18px] font-semibold text-gray-900">Comparativo por Sede</h1>
        <form method="GET" class="flex items-center gap-2">
            <select name="mes" class="px-3 py-1.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                @foreach($meses as $num => $nombre)
                <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ ucfirst($nombre) }}</option>
                @endforeach
            </select>
            <select name="anio" class="px-3 py-1.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                @foreach($anios as $y)
                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3.5 py-1.5 bg-stock-primary text-white text-[13px] font-medium rounded-lg hover:bg-stock-primary/90 transition">Ver</button>
        </form>
    </div>

    {{-- Cards --}}
    @php $grandTotal = collect($sedes)->sum('total_ventas'); @endphp
    <div class="grid grid-cols-3 gap-4">
        @forelse($sedes as $sede)
        @php $pct = $grandTotal > 0 ? round(($sede['total_ventas'] / $grandTotal) * 100, 1) : 0; @endphp
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-[14px] font-semibold text-gray-800">{{ $sede['nombre'] }}</h3>
                <span class="text-[11px] font-semibold text-gray-400">{{ $pct }}%</span>
            </div>
            <p class="text-[22px] font-bold text-stock-primary tabular-nums">${{ number_format($sede['total_ventas'], 0) }}</p>
            <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-stock-primary/70 rounded-full" style="width:{{ $pct }}%"></div>
            </div>
            <div class="mt-3 flex justify-between text-[11px] text-gray-500">
                <span>{{ number_format($sede['transacciones']) }} tx</span>
                <span>Prom. ${{ number_format($sede['promedio_venta'], 0) }}</span>
            </div>
        </div>
        @empty
        <div class="col-span-3 bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] px-5 py-12 text-center">
            <p class="text-[14px] font-medium text-gray-500">Sin datos para el período seleccionado</p>
        </div>
        @endforelse
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100 bg-gray-50/50">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Total Ventas</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Transacciones</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Promedio</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Participación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sedes as $sede)
                @php $pct = $grandTotal > 0 ? ($sede['total_ventas'] / $grandTotal) * 100 : 0; @endphp
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $sede['nombre'] }}</td>
                    <td class="px-5 py-3 text-right font-bold text-stock-primary">${{ number_format($sede['total_ventas'], 2) }}</td>
                    <td class="px-5 py-3 text-center text-gray-700">{{ number_format($sede['transacciones']) }}</td>
                    <td class="px-5 py-3 text-right text-gray-600">${{ number_format($sede['promedio_venta'], 2) }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-stock-primary/70 rounded-full" style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="text-[11px] text-gray-500 w-10 text-right">{{ number_format($pct, 1) }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-[13px] text-gray-400">Sin datos</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
