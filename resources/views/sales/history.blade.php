@extends('layouts.app')

@section('title', 'Historial de Ventas')

@section('content')
<div class="space-y-6">
    <h1 class="text-[18px] font-semibold text-gray-900">Historial de ventas</h1>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left py-3 px-5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha / Hora</th>
                    <th class="text-left py-3 px-5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operador</th>
                    <th class="text-left py-3 px-5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Productos</th>
                    <th class="text-right py-3 px-5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Descuento</th>
                    <th class="text-right py-3 px-5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sales as $sale)
                    <tr class="hover:bg-gray-50 transition" x-data="{ open: false }">
                        <td class="py-3 px-5 text-gray-500 whitespace-nowrap">{{ $sale->fecha_venta->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-5 text-gray-600">{{ $sale->user?->name ?? '—' }}</td>
                        <td class="py-3 px-5">
                            <button @click="open = !open" class="text-blue-600 hover:underline text-xs focus:outline-none">
                                {{ $sale->items->count() }} producto(s) <span x-text="open ? '▲' : '▼'"></span>
                            </button>
                            <div x-show="open" class="mt-2 space-y-1">
                                @foreach($sale->items as $item)
                                    <div class="text-xs text-gray-600 flex justify-between">
                                        <span>{{ $item->product?->nombre ?? '(producto eliminado)' }} ×{{ $item->cantidad }}</span>
                                        <span class="ml-4">{{ formatCurrency($item->subtotal) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 px-5 text-right text-red-500">{{ formatCurrency(0 - ($sale->descuento ?? 0)) }}</td>
                        <td class="py-3 px-5 text-right font-semibold text-gray-900">{{ formatCurrency($sale->total_sistema) }}</td>
                    </tr>
                @empty
                    <x-empty-state :colspan="5" icon="cart" title="Sin ventas registradas"
                        description="Las ventas aparecerán aquí una vez procesadas"/>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $sales->links() }}
        </div>
    </div>
</div>
@endsection
