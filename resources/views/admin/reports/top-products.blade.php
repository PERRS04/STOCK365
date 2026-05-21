@extends('layouts.app')

@section('title', 'Top Productos')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Top Productos Más Vendidos</h1>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 text-sm font-medium transition">Filtrar</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700 w-12">#</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Producto</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Marca</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Tamaño</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Unidades Vendidas</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Ingresos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($topProducts as $i => $product)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-center">
                            @if($i === 0)
                                <span class="text-yellow-500 font-bold">1°</span>
                            @elseif($i === 1)
                                <span class="text-gray-400 font-bold">2°</span>
                            @elseif($i === 2)
                                <span class="text-orange-400 font-bold">3°</span>
                            @else
                                <span class="text-gray-500">{{ $i + 1 }}°</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $product->nombre }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $product->marca }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $product->tamaño }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-bold">{{ $product->total_vendido }}</span>
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-stock-primary">{{ formatCurrency($product->total_ingresos) }}</td>
                    </tr>
                @empty
                    <x-empty-state :colspan="6" icon="tag" title="Sin datos disponibles"
                        description="Las ventas generarán el ranking de productos"/>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
