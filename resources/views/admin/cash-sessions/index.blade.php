@extends('layouts.app')

@section('title', 'Sesiones de Caja')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Sesiones de Caja</h1>
            <p class="text-sm text-gray-500 mt-1">Control de cajas abiertas y historial de sesiones</p>
        </div>
    </div>

    {{-- Active Sessions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
            <h2 class="font-semibold text-gray-800">Cajas Activas Ahora</h2>
            <span class="ml-auto text-sm font-semibold text-green-600">{{ $activeSessions->count() }} activa(s)</span>
        </div>

        @if($activeSessions->isEmpty())
            <div class="px-6 py-10 text-center text-gray-400 text-sm">
                No hay cajas abiertas en este momento.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Operador</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sede</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Apertura</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Monto inicial</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Duración</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($activeSessions as $session)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $session->user->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $session->sede->nombre }}</td>
                            <td class="px-6 py-4">
                                @if($session->isOpen())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Abierta
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                        Cierre pendiente
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $session->opened_at->format('d/m H:i') }}</td>
                            <td class="px-6 py-4 font-mono text-gray-800">${{ number_format($session->opening_amount, 2) }}</td>
                            <td class="px-6 py-4 font-mono text-gray-600">{{ $session->duration }}</td>
                            <td class="px-6 py-4">
                                @can('closings.approve')
                                <form method="POST" action="{{ route('cash-sessions.force-close', $session) }}"
                                      data-confirm="¿Cerrar forzosamente la caja de {{ $session->user->name }}?">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="text-xs text-red-600 hover:text-red-800 border border-red-200 hover:border-red-400 px-3 py-1 rounded transition">
                                        Cerrar forzoso
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent Closed Sessions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Historial de Sesiones Cerradas</h2>
        </div>

        @if($recentClosed->isEmpty())
            <div class="px-6 py-10 text-center text-gray-400 text-sm">
                Sin historial de sesiones cerradas.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Operador</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sede</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Apertura</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Cierre</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Duración</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Monto inicial</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentClosed as $session)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $session->user->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $session->sede->nombre }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $session->opened_at->format('d/m H:i') }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $session->closed_at?->format('d/m H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 font-mono text-gray-600">{{ $session->duration }}</td>
                            <td class="px-6 py-4 font-mono text-gray-800">${{ number_format($session->opening_amount, 2) }}</td>
                            <td class="px-6 py-4 text-gray-500 max-w-xs truncate">{{ $session->notes ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $recentClosed->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
