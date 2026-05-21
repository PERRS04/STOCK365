@extends('layouts.app')

@section('title', 'Pedidos de Compra')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Pedidos de Compra</h1>
        <a href="{{ route('purchase-orders.create') }}" class="bg-stock-primary text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition font-medium">
            + Nuevo Pedido
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">#</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Proveedor</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Fecha Pedido</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Entrega Est.</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Total</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Estado</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-gray-500">#{{ $order->id }}</td>
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $order->provider->nombre }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $order->fecha_pedido->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $order->fecha_estimada_entrega ? $order->fecha_estimada_entrega->format('d/m/Y') : '—' }}</td>
                        <td class="py-3 px-4 text-right font-semibold">{{ formatCurrency($order->total) }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($order->estado === 'pendiente')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Pendiente</span>
                            @elseif($order->estado === 'enviado')
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Enviado</span>
                            @else
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Recibido</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                @if($order->estado === 'pendiente')
                                    <form action="{{ route('purchase-orders.send', $order) }}" method="POST"
                                          data-confirm="¿Marcar el pedido #{{ $order->id }} como enviado?">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs font-medium transition">Marcar Enviado</button>
                                    </form>
                                @elseif($order->estado === 'enviado')
                                    <a href="{{ route('purchase-orders.show', $order) }}" class="text-green-600 hover:text-green-800 text-xs font-medium transition">Recibir Stock</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty-state :colspan="7" icon="document" title="Sin pedidos registrados"
                        description="Crea tu primer pedido de compra"/>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
