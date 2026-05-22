@extends('layouts.app')

@section('title', 'Cierres Pendientes')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Cierres de Caja Pendientes</h1>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Fecha</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Sede</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Operador</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Total Sistema</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Efectivo</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Diferencia</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($closings as $closing)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-gray-500">{{ $closing->fecha_cierre->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-4 font-medium">{{ $closing->sede?->nombre ?? '—' }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $closing->user?->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-right font-semibold">{{ formatCurrency($closing->total_sistema) }}</td>
                        <td class="py-3 px-4 text-right">{{ formatCurrency($closing->efectivo) }}</td>
                        <td class="py-3 px-4 text-right font-bold {{ abs($closing->diferencia) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                            {{ formatCurrency($closing->diferencia) }}
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center justify-center space-x-2">
                                <form action="{{ route('cash-closings.approve', $closing) }}" method="POST"
                                      data-confirm="¿Aprobar el cierre de {{ $closing->user?->name ?? 'operador' }}?">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700 transition">Aprobar</button>
                                </form>
                                <form action="{{ route('cash-closings.reject', $closing) }}" method="POST"
                                      data-confirm="¿Rechazar el cierre de {{ $closing->user->name }}?">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700 transition">Rechazar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty-state :colspan="7" icon="clock" title="Sin cierres pendientes"
                        description="Todos los cierres han sido procesados"/>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $closings->links() }}
        </div>
    </div>
</div>
@endsection
