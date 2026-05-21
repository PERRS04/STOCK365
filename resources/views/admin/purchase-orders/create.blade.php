@extends('layouts.app')

@section('title', 'Nuevo Pedido')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="purchaseOrder()">
    <div class="flex items-center space-x-4">
        <a href="{{ route('purchase-orders.index') }}" class="text-gray-500 hover:text-gray-700">← Volver</a>
        <h1 class="text-2xl font-bold text-gray-800">Nuevo Pedido de Compra</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('purchase-orders.store') }}" method="POST" class="space-y-6">
            @csrf

            @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-600 text-sm">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor *</label>
                    <select name="provider_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                        <option value="">Seleccionar proveedor</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Estimada Entrega</label>
                    <input type="date" name="fecha_estimada_entrega" min="{{ now()->addDay()->format('Y-m-d') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
            </div>

            <!-- Items -->
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-gray-700">Productos</h3>
                    <button type="button" @click="addItem()" class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg font-medium">+ Agregar Producto</button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <select :name="`items[${index}][product_id]`" required class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Producto...</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->nombre }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                            <input type="number" :name="`items[${index}][cantidad]`" x-model="item.cantidad" min="1" placeholder="Cant."
                                class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <input type="number" step="0.01" :name="`items[${index}][precio_unitario]`" x-model="item.precio" placeholder="P. Unit."
                                class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <button type="button" @click="items.splice(index, 1)" class="text-red-500 hover:text-red-700 p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-3 text-right font-semibold text-gray-700">
                    Total: <span x-text="formatCurrency(total)">$0.00</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 font-medium">Crear Pedido</button>
            </div>
        </form>
    </div>
</div>

<script>
function purchaseOrder() {
    return {
        items: [{ cantidad: 1, precio: 0 }],
        get total() {
            return this.items.reduce((sum, i) => sum + (parseFloat(i.cantidad) || 0) * (parseFloat(i.precio) || 0), 0);
        },
        addItem() {
            this.items.push({ cantidad: 1, precio: 0 });
        }
    }
}
</script>
@endsection
