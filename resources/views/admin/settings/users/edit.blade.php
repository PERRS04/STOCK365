@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="max-w-lg mx-auto space-y-6" x-data="{ role: '{{ old('role', $user->role ?? 'operador') }}' }">
    <div class="flex items-center space-x-4">
        <a href="{{ route('users.index') }}" class="text-gray-500 hover:text-gray-700">← Volver</a>
        <h1 class="text-2xl font-bold text-gray-800">Editar: {{ $user->name }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                <select name="role" x-model="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                    <option value="operador" {{ $user->role === 'operador' ? 'selected' : '' }}>Operador</option>
                    <option value="supervisor" {{ $user->role === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                    <option value="boss" {{ $user->role === 'boss' ? 'selected' : '' }}>BOSS (Administrador)</option>
                </select>
            </div>
            <div x-show="role === 'operador'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                <select name="sede_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                    <option value="">Sin sede</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ old('sede_id', $user->sede_id) == $sede->id ? 'selected' : '' }}>{{ $sede->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="active" id="active" value="1" {{ $user->active ? 'checked' : '' }} class="h-4 w-4 text-stock-primary border-gray-300 rounded">
                <label for="active" class="ml-2 text-sm text-gray-700">Usuario activo</label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('users.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 font-medium">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
