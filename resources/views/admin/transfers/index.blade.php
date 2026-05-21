@extends('layouts.app')
@section('title', 'Transferencias')
@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-[18px] font-semibold text-gray-900">Transferencias entre Sedes</h1>
        <a href="{{ route('transfers.create') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-stock-primary text-white text-[13px] font-medium hover:bg-stock-primary/90 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Transferencia
        </a>
    </div>

    {{-- Filter tabs --}}
    <div class="flex gap-2">
        @foreach(['' => 'Todas', 'pendiente' => 'Pendientes', 'aprobado' => 'Aprobadas', 'rechazado' => 'Rechazadas'] as $val => $label)
        <a href="{{ route('transfers.index', $val ? ['estado' => $val] : []) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border text-[12px] font-medium transition
                  {{ request('estado', '') === $val
                     ? match($val) { 'pendiente' => 'bg-amber-50 border-amber-300 text-amber-800', 'aprobado' => 'bg-emerald-50 border-emerald-300 text-emerald-800', 'rechazado' => 'bg-red-50 border-red-300 text-red-800', default => 'bg-gray-100 border-gray-300 text-gray-800' }
                     : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            @if($val === 'pendiente' && $pendingCount > 0)
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            @endif
            {{ $label }}
            @if($val === 'pendiente' && $pendingCount > 0)
                <span class="font-bold">{{ $pendingCount }}</span>
            @endif
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Origen</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Destino</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Creado por</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Estado</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($transfers as $transfer)
                @php
                    $stateMap = [
                        'pendiente' => ['bg-amber-50 text-amber-700 border-amber-200', 'Pendiente'],
                        'aprobado'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Aprobado'],
                        'rechazado' => ['bg-red-50 text-red-700 border-red-200', 'Rechazado'],
                    ];
                    [$stateCls, $stateLabel] = $stateMap[$transfer->estado] ?? ['bg-gray-100 text-gray-600 border-gray-200', $transfer->estado];
                @endphp
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 text-gray-500 text-[12px] whitespace-nowrap">{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 font-medium text-gray-900">{{ $transfer->fromSede->nombre }}</td>
                    <td class="px-5 py-3 text-gray-700">→ {{ $transfer->toSede->nombre }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $transfer->createdBy->name }}</td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $stateCls }}">{{ $stateLabel }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('transfers.show', $transfer) }}" class="text-[12px] font-medium text-stock-primary hover:underline">
                            {{ $transfer->isPending() ? 'Revisar' : 'Ver' }} →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-[13px] text-gray-400">Sin transferencias registradas</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $transfers->links() }}
        </div>
    </div>

</div>
@endsection
