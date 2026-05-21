@extends('layouts.app')

@section('title', 'Reporte de Utilidades')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Reporte de Utilidades</h1>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Sede</label>
                <select name="sede_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todas</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ request('sede_id') == $sede->id ? 'selected' : '' }}>{{ $sede->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 text-sm font-medium transition">Filtrar</button>
        </form>
    </div>

    <!-- Resumen -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-stock-primary">
            <p class="text-sm text-gray-600">Ingresos Totales</p>
            <p class="text-2xl font-bold text-stock-primary">{{ formatCurrency($profitability->sum('total_venta')) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-400">
            <p class="text-sm text-gray-600">Costo Total</p>
            <p class="text-2xl font-bold text-red-500">{{ formatCurrency($profitability->sum('total_costo')) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-600">Utilidad Bruta</p>
            <p class="text-2xl font-bold text-green-600">{{ formatCurrency($profitability->sum('utilidad_bruta')) }}</p>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Producto</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Marca</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Unidades</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Ingreso</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Costo</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Utilidad</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Margen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($profitability as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $item->nombre }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $item->marca }}</td>
                        <td class="py-3 px-4 text-center">{{ $item->total_cantidad }}</td>
                        <td class="py-3 px-4 text-right">{{ formatCurrency($item->total_venta) }}</td>
                        <td class="py-3 px-4 text-right text-red-500">{{ formatCurrency($item->total_costo) }}</td>
                        <td class="py-3 px-4 text-right font-bold {{ $item->utilidad_bruta >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ formatCurrency($item->utilidad_bruta) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @php $margen = $item->total_venta > 0 ? ($item->utilidad_bruta / $item->total_venta) * 100 : 0; @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $margen >= 20 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ number_format($margen, 1) }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <x-empty-state :colspan="7" icon="chart" title="Sin datos para el período"
                        description="Ajusta los filtros para ver resultados"/>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
