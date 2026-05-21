@extends('layouts.app')
@section('title', 'Editar Proveedor')
@section('content')

<div class="max-w-2xl space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('suppliers.show', $supplier) }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← {{ $supplier->nombre }}</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Editar Proveedor</h1>
    </div>

    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-6 space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Nombre del Proveedor *</label>
                <input type="text" name="nombre" value="{{ old('nombre', $supplier->nombre) }}" required
                       class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary @error('nombre') border-red-300 @enderror">
                @error('nombre') <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">RUC / NIT</label>
                <input type="text" name="ruc_nit" value="{{ old('ruc_nit', $supplier->ruc_nit) }}"
                       class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary @error('ruc_nit') border-red-300 @enderror">
                @error('ruc_nit') <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Contacto Principal</label>
                <input type="text" name="contacto_principal" value="{{ old('contacto_principal', $supplier->contacto_principal) }}"
                       class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $supplier->telefono) }}"
                       class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}"
                       class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary @error('email') border-red-300 @enderror">
                @error('email') <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Dirección</label>
                <input type="text" name="direccion" value="{{ old('direccion', $supplier->direccion) }}"
                       class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
            </div>

            <div class="col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Observaciones</label>
                <textarea name="observaciones" rows="3"
                          class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary resize-none">{{ old('observaciones', $supplier->observaciones) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
            @if($supplier->activo)
            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('¿Desactivar este proveedor?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-[12px] text-red-500 hover:text-red-700 transition">Desactivar proveedor</button>
            </form>
            @else
            <span class="text-[12px] text-gray-400">Proveedor inactivo</span>
            @endif

            <div class="flex gap-3">
                <a href="{{ route('suppliers.show', $supplier) }}" class="px-4 py-2 text-[13px] text-gray-600 hover:text-gray-900 transition">Cancelar</a>
                <button type="submit" class="px-5 py-2 bg-stock-primary text-white text-[13px] font-medium rounded-lg hover:bg-stock-primary/90 transition">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </form>

</div>
@endsection
