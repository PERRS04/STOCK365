@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('products.index') }}" class="text-gray-500 hover:text-gray-700">← Volver</a>
        <h1 class="text-2xl font-bold text-gray-800">Editar: {{ $product->nombre }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-600 text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" value="{{ $product->sku }}" disabled
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $product->nombre) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marca *</label>
                    <input type="text" name="marca" value="{{ old('marca', $product->marca) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamaño *</label>
                    <input type="text" name="tamaño" value="{{ old('tamaño', $product->tamaño) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio Compra (USD) *</label>
                    <input type="number" step="0.01" name="precio_compra" value="{{ old('precio_compra', $product->precio_compra) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio Venta (USD) *</label>
                    <input type="number" step="0.01" name="precio_venta" value="{{ old('precio_venta', $product->precio_venta) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo *</label>
                    <input type="number" name="stock_minimo" value="{{ old('stock_minimo', $product->stock_minimo) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Imagen</label>
                    <input type="file" name="imagen" accept="image/*"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                    @if($product->imagen)
                        <p class="text-xs text-gray-500 mt-1">Imagen actual guardada</p>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">{{ old('descripcion', $product->descripcion) }}</textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('products.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 font-medium">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
