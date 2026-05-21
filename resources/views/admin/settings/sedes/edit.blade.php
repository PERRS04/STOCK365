@extends('layouts.app')

@section('title', 'Editar Sede')

@section('content')
<div class="max-w-lg mx-auto space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('sedes.index') }}" class="text-gray-500 hover:text-gray-700">← Volver</a>
        <h1 class="text-2xl font-bold text-gray-800">Editar: {{ $sede->nombre }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('sedes.update', $sede) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-600 text-sm">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="nombre" value="{{ old('nombre', $sede->nombre) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad *</label>
                <input type="text" name="ciudad" value="{{ old('ciudad', $sede->ciudad) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación / Dirección</label>
                <input type="text" name="ubicacion" value="{{ old('ubicacion', $sede->ubicacion) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $sede->telefono) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $sede->email) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="activa" id="activa" value="1" {{ $sede->activa ? 'checked' : '' }} class="h-4 w-4 text-stock-primary border-gray-300 rounded">
                <label for="activa" class="ml-2 text-sm text-gray-700">Sede activa</label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('sedes.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 font-medium">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
