@extends('layouts.app')
@section('title', $almacen->nombre)
@section('content')

<div class="space-y-5">

    {{-- Breadcrumb + header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('almacenes.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Almacenes</a>
            <span class="text-gray-200">/</span>
            <h1 class="text-[18px] font-semibold text-gray-900">{{ $almacen->nombre }}</h1>
            @if($almacen->activo)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-medium bg-emerald-50 text-emerald-700 border-emerald-200">Activo</span>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-medium bg-gray-100 text-gray-500 border-gray-200">Inactivo</span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('almacenes.movements', $almacen) }}"
               class="px-4 py-2 text-[13px] text-gray-600 border border-gray-200 rounded-lg hover:border-gray-300 hover:text-gray-900 transition">
                Ver movimientos →
            </a>
            <a href="{{ route('transfers.create') }}"
               class="px-4 py-2 bg-stock-primary text-white text-[13px] font-medium rounded-lg hover:bg-stock-primary/90 transition">
                Nueva transferencia
            </a>
        </div>
    </div>

    @if($almacen->descripcion)
    <p class="text-[13px] text-gray-500">{{ $almacen->descripcion }}</p>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-4 max-w-sm">
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Productos</p>
            <p class="text-[24px] font-bold text-gray-900">{{ $totalProductos }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Stock total</p>
            <p class="text-[24px] font-bold text-gray-900">{{ number_format($totalStock) }}</p>
        </div>
    </div>

    {{-- Inventory table --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-[13px] font-semibold text-gray-900">Inventario</h2>
            <span class="text-[12px] text-gray-400">{{ $inventories->total() }} productos</span>
        </div>

        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">SKU</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Marca</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Stock Actual</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Stock Rec.</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($inventories as $item)
                @php
                    $esStockBajo = $item->stock_recomendado !== null && $item->cantidad_stock < $item->stock_recomendado;
                @endphp
                <tr class="hover:bg-gray-50/50 {{ $esStockBajo ? 'bg-red-50/40 hover:bg-red-50' : '' }}">
                    <td class="px-5 py-3 font-mono text-[11px] text-gray-400">{{ $item->product->sku }}</td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800">{{ $item->product->nombre }}</p>
                        @if($item->product->tamaño)
                            <p class="text-[11px] text-gray-400">{{ $item->product->tamaño }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $item->product->marca ?? '—' }}</td>
                    <td class="px-5 py-3 text-center font-bold {{ $esStockBajo ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $item->cantidad_stock }}
                    </td>
                    <td class="px-5 py-3 text-center text-gray-500">
                        {{ $item->stock_recomendado ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($item->stock_recomendado === null)
                            <span class="text-[11px] text-gray-400">—</span>
                        @elseif($esStockBajo)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium bg-red-50 text-red-700 border-red-200">Stock Bajo</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium bg-emerald-50 text-emerald-700 border-emerald-200">OK</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-[13px] text-gray-400">Sin registros de inventario para este almacén</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $inventories->links() }}
        </div>
    </div>

</div>
@endsection
