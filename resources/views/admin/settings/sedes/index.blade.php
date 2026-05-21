@extends('layouts.app')

@section('title', 'Sedes')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Sedes</h1>
        <a href="{{ route('sedes.create') }}" class="bg-stock-primary text-white px-4 py-2 rounded-lg hover:bg-blue-800 font-medium">
            + Nueva Sede
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nombre</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ciudad</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ubicación</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Teléfono</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Estado</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sedes as $sede)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $sede->nombre }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $sede->ciudad }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $sede->ubicacion ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $sede->telefono ?? '—' }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($sede->activa)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Activa</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">Inactiva</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('sedes.edit', $sede) }}" class="text-blue-600 hover:text-blue-800 font-medium">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">No hay sedes registradas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $sedes->links() }}
        </div>
    </div>
</div>
@endsection
