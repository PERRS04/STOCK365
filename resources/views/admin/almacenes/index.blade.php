@extends('layouts.app')

@section('title', 'Almacenes')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Almacenes</h1>
            <p class="text-sm text-gray-500 mt-1">Ubicaciones físicas de almacenamiento independientes.</p>
        </div>
        <a href="{{ route('almacenes.create') }}"
           class="bg-stock-primary text-white px-4 py-2 rounded-lg hover:bg-blue-800 font-medium">
            + Nuevo Almacén
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nombre</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Descripción</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Estado</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($almacenes as $almacen)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $almacen->nombre }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $almacen->descripcion ?? '—' }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($almacen->activo)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Activo</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-medium">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('almacenes.edit', $almacen) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-gray-500">
                            No hay almacenes registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $almacenes->links() }}
        </div>
    </div>
</div>
@endsection
