@extends('layouts.app')

@section('title', 'Nuevo Usuario')

@section('content')
<div class="max-w-lg mx-auto space-y-6"
     x-data="{
        role:      '{{ old('role', 'operador') }}',
        locType:   '{{ old('location_type', 'sede') }}',
        sedeId:    '{{ old('sede_id', '') }}',
        almacenId: '{{ old('almacen_id', '') }}',
        init() {
            this.$watch('locType', (val) => {
                if (val !== 'sede')    this.sedeId    = '';
                if (val !== 'almacen') this.almacenId = '';
            });
        }
     }">
    <div class="flex items-center space-x-4">
        <a href="{{ route('users.index') }}" class="text-gray-500 hover:text-gray-700">← Volver</a>
        <h1 class="text-2xl font-bold text-gray-800">Nuevo Usuario</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf

            @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-600 text-sm">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña *</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                <select name="role" x-model="role" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                    <option value="operador">Operador</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="boss">BOSS (Administrador)</option>
                </select>
            </div>

            {{--
                Location section — always present in DOM for ALL roles.
                "Sin ubicación" is only shown for non-operador via x-if (removes from DOM),
                preventing hidden radio buttons from submitting stale values.
                Alpine watchers clear the inactive select when locType changes.
            --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de trabajo</label>
                <div class="flex gap-4 mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="location_type" value="sede"
                               x-model="locType" class="text-stock-primary">
                        <span class="text-sm text-gray-700">Sede</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="location_type" value="almacen"
                               x-model="locType" class="text-stock-primary">
                        <span class="text-sm text-gray-700">Depósito</span>
                    </label>
                    {{-- x-if removes this radio from the DOM when role is operador,
                         preventing it from submitting a stale empty value. --}}
                    <template x-if="role !== 'operador'">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="location_type" value=""
                                   x-model="locType" class="text-stock-primary">
                            <span class="text-sm text-gray-700">Sin ubicación</span>
                        </label>
                    </template>
                </div>

                <p x-show="role === 'operador' && locType === ''"
                   x-cloak
                   class="text-amber-600 text-sm mb-2">
                    Un operador debe tener Sede o Depósito asignado.
                </p>

                <div x-show="locType === 'sede'">
                    <select name="sede_id" x-model="sedeId"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                        <option value="">Seleccionar sede</option>
                        @foreach($sedes as $sede)
                            <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="locType === 'almacen'">
                    <select name="almacen_id" x-model="almacenId"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                        <option value="">Seleccionar depósito</option>
                        @foreach($almacenes as $almacen)
                            <option value="{{ $almacen->id }}">{{ $almacen->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('users.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-stock-primary text-white rounded-lg hover:bg-blue-800 font-medium">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>
@endsection
