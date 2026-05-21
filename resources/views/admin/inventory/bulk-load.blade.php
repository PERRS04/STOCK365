@extends('layouts.app')
@section('title', 'Carga Masiva de Stock')
@section('content')

<div class="space-y-5" x-data="bulkLoader()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Inventario</a>
            <span class="text-gray-200">/</span>
            <h1 class="text-[18px] font-semibold text-gray-900">Carga Masiva de Stock</h1>
        </div>
        @if($sede)
        <div class="flex items-center gap-2">
            <span class="text-[12px] text-gray-500">Sede:</span>
            <span class="text-[13px] font-semibold text-gray-900">{{ $sede->nombre }}</span>
        </div>
        @endif
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[13px] text-emerald-700">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Sede selector --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] px-5 py-4">
        <form method="GET" class="flex items-end gap-4">
            <div class="flex-1 max-w-xs">
                <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Seleccionar Sede</label>
                <select name="sede_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                    @foreach($sedes as $s)
                    <option value="{{ $s->id }}" {{ $sedeId == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-[12px] text-gray-400 pb-2">Ingresa las cantidades para cada producto. Solo se actualizan los que cambian.</p>
        </form>
    </div>

    @if($sede)
    {{-- Bulk form --}}
    <form method="POST" action="{{ route('inventory.bulk-save') }}" id="bulkForm">
        @csrf
        <input type="hidden" name="sede_id" value="{{ $sede->id }}">

        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">

            {{-- Toolbar --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <span class="text-[13px] font-semibold text-gray-800">{{ $products->count() }} productos</span>
                    <span class="text-[11px] text-gray-400">·</span>
                    <button type="button" @click="clearAll()" class="text-[12px] text-gray-400 hover:text-gray-600 transition">Limpiar todo</button>
                    <button type="button" @click="fillZero()" class="text-[12px] text-gray-400 hover:text-gray-600 transition">Poner todo en 0</button>
                </div>
                <div class="flex items-center gap-3">
                    <div>
                        <input type="text" name="motivo" placeholder="Motivo (ej: Inventario inicial apertura)"
                               value="{{ old('motivo', 'Inventario inicial') }}"
                               class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg w-64 focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                    </div>
                    <button type="submit"
                            class="px-5 py-2 bg-stock-primary text-white text-[13px] font-semibold rounded-lg hover:bg-stock-primary/90 transition">
                        Guardar Stock
                    </button>
                </div>
            </div>

            <table class="w-full text-[13px]">
                <thead class="border-b border-gray-100 bg-gray-50/50">
                    <tr>
                        <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                        <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">SKU</th>
                        <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Precio Venta</th>
                        <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Stock Actual</th>
                        <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 w-48">Nueva Cantidad</th>
                        <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Diferencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($products as $product)
                    @php $current = (int)($currentStock[$product->id] ?? 0); @endphp
                    <tr class="hover:bg-gray-50/40" x-data="{ qty: {{ $current }}, original: {{ $current }} }">
                        <td class="px-5 py-2.5">
                            <p class="font-medium text-gray-900">{{ $product->nombre }}</p>
                            <p class="text-[11px] text-gray-400">{{ $product->marca }} · {{ $product->tamaño }}</p>
                        </td>
                        <td class="px-5 py-2.5 font-mono text-[12px] text-gray-500">{{ $product->sku }}</td>
                        <td class="px-5 py-2.5 text-right text-gray-700">${{ number_format($product->precio_venta, 2) }}</td>
                        <td class="px-5 py-2.5 text-right font-semibold {{ $current <= $product->stock_minimo ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $current }}
                        </td>
                        <td class="px-5 py-2.5 text-center">
                            <input type="number"
                                   name="quantities[{{ $product->id }}]"
                                   x-model.number="qty"
                                   min="0"
                                   placeholder="{{ $current }}"
                                   class="w-28 px-3 py-1.5 text-center text-[13px] font-semibold border rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition"
                                   :class="qty !== original ? 'border-stock-primary bg-blue-50/50' : 'border-gray-200'">
                        </td>
                        <td class="px-5 py-2.5 text-right tabular-nums font-semibold text-[13px]"
                            :class="(qty - original) > 0 ? 'text-emerald-600' : ((qty - original) < 0 ? 'text-red-600' : 'text-gray-300')">
                            <span x-text="(qty - original) > 0 ? '+' + (qty - original) : (qty - original) === 0 ? '—' : (qty - original)"></span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-gray-200 bg-gray-50/50">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-[12px] font-semibold text-gray-600">
                            Total unidades actuales
                        </td>
                        <td class="px-5 py-3 text-right font-bold text-gray-900">
                            {{ $currentStock->sum() }}
                        </td>
                        <td colspan="2" class="px-5 py-3 text-right">
                            <button type="submit" class="px-5 py-2 bg-stock-primary text-white text-[13px] font-semibold rounded-lg hover:bg-stock-primary/90 transition">
                                Guardar Stock
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>

        </div>
    </form>
    @endif

</div>

@push('scripts')
<script>
function bulkLoader() {
    return {
        clearAll() {
            document.querySelectorAll('#bulkForm input[type=number]').forEach(i => {
                i.__x && (i.__x.$data.qty = i.__x.$data.original);
            });
        },
        fillZero() {
            document.querySelectorAll('#bulkForm input[type=number]').forEach(i => {
                if (i.__x) i.__x.$data.qty = 0;
                else { i.value = 0; i.dispatchEvent(new Event('input')); }
            });
        }
    }
}
</script>
@endpush

@endsection
