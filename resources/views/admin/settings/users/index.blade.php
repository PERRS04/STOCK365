@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Usuarios</h1>
        <a href="{{ route('users.create') }}" class="bg-stock-primary text-white px-4 py-2 rounded-lg hover:bg-blue-800 font-medium">
            + Nuevo Usuario
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nombre</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Rol</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Sede</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Estado</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $user->name }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $user->email }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($user->role === 'boss')
                                <span class="px-2 py-1 bg-stock-primary text-white rounded-full text-xs font-medium">BOSS</span>
                            @elseif($user->role === 'supervisor')
                                <span class="px-2 py-1 bg-blue-600 text-white rounded-full text-xs font-medium">Supervisor</span>
                            @else
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Operador</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-gray-600">{{ $user->sede?->nombre ?? '—' }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($user->active)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Activo</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center space-x-3">
                                <a href="{{ route('users.edit', $user) }}" class="text-blue-600 hover:text-blue-800 font-medium">Editar</a>
                                @if($user->active && $user->id !== auth()->id())
                                    <form action="{{ route('users.deactivate', $user) }}" method="POST" onsubmit="return confirm('¿Desactivar este usuario?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Desactivar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">No hay usuarios registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
