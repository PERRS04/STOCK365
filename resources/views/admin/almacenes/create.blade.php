@extends('layouts.app')

@section('title', 'Nuevo Almacén')

@section('content')
<div class="max-w-lg mx-auto space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('almacenes.index') }}" class="text-gray-500 hover:text-gray-700">← Volver</a>
        <h1 class="text-2xl font-bold text-gray-800">Nuevo Almacén</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('almacenes.store') }}" method="POST" class="space-y-4">
            @csrf

            @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-600 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary"
                    placeholder="Ej: Almacén Sauces">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary"
                    placeholder="Descripción opcional del almacén">{{ old('descripcion') }}</textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('almacenes.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 font-medium">
                    Crear Almacén
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
