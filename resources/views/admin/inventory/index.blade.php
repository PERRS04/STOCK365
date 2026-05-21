@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
<div
    x-data="{ open: false }"
    class="space-y-6"
>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Inventario</h1>
        <div class="flex items-center gap-3">
            <button
                @click="open = true"
                class="bg-stock-accent text-stock-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-yellow-400 transition">
                Ajustar Stock
            </button>
            <a href="{{ route('inventory.movements') }}" class="text-sm text-stock-primary hover:underline">Ver Movimientos →</a>
        </div>
    </div>

    {{-- Livewire table + filters --}}
    <livewire:inventory.inventory-table />

    {{-- Adjust Stock Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Ajustar Stock</h3>
            <form action="{{ route('inventory.adjust') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                    <select name="sede_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                        @foreach($sedes as $sede)
                            <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                    <select name="product_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->nombre }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad (+/-)</label>
                    <input type="number" name="cantidad" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                    <p class="text-xs text-gray-500 mt-1">Positivo para entrada, negativo para salida</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo *</label>
                    <input type="text" name="motivo" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary"></textarea>
                </div>
                <div class="flex justify-end space-x-3 pt-2">
                    <button type="button" @click="open = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 transition">
                        Ajustar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
