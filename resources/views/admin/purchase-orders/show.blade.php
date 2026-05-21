@extends('layouts.app')

@section('title', 'Recibir Pedido')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('purchase-orders.index') }}" class="text-gray-500 hover:text-gray-700">← Volver</a>
        <h1 class="text-2xl font-bold text-gray-800">Recibir Pedido #{{ $purchaseOrder->id ?? $order->id }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-4 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Proveedor</p>
                <p class="font-semibold">{{ ($purchaseOrder ?? $order)->provider->nombre }}</p>
            </div>
            <div>
                <p class="text-gray-500">Fecha Pedido</p>
                <p class="font-semibold">{{ ($purchaseOrder ?? $order)->fecha_pedido->format('d/m/Y') }}</p>
            </div>
        </div>

        <form action="{{ route('purchase-orders.receive', $purchaseOrder ?? $order) }}" method="POST" class="space-y-4">
            @csrf

            <h3 class="font-semibold text-gray-700 border-b pb-2">Cantidades Recibidas</h3>

            @foreach(($purchaseOrder ?? $order)->items as $item)
                <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <p class="font-medium">{{ $item->product->nombre }}</p>
                        <p class="text-xs text-gray-500">Pedido: {{ $item->cantidad }} unidades</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Recibido</label>
                        <input type="number" name="items[{{ $loop->index }}][cantidad_recibida]" value="{{ $item->cantidad }}" min="0" max="{{ $item->cantidad }}"
                            class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Confirmar Recepción</button>
            </div>
        </form>
    </div>
</div>
@endsection
