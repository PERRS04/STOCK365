@extends('layouts.app')
@section('title', 'Aprobaciones Pendientes')
@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Aprobaciones Pendientes</h1>
            <p class="text-[12px] text-gray-400 mt-0.5">Todas las solicitudes que requieren revisión</p>
        </div>
        @if($totalPending > 0)
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-[12px] font-semibold text-amber-700">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            {{ $totalPending }} pendiente{{ $totalPending !== 1 ? 's' : '' }}
        </span>
        @else
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-[12px] font-semibold text-emerald-700">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Al día
        </span>
        @endif
    </div>

    @if($totalPending === 0)
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card p-12 text-center">
        <svg class="w-12 h-12 text-emerald-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-[15px] font-medium text-gray-600">Sin pendientes</p>
        <p class="text-[12px] text-gray-400 mt-1">No hay cortesías ni movimientos esperando aprobación.</p>
    </div>
    @endif

    {{-- Cortesías pendientes --}}
    @can('courtesies.approve')
    @if($pendingCourtesies->count() > 0)
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                {{-- Gift icon --}}
                <svg class="w-4 h-4 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                </svg>
                <h2 class="text-[13px] font-semibold text-gray-800">Cortesías</h2>
                <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-pink-100 text-pink-700">{{ $pendingCourtesies->count() }}</span>
            </div>
            <a href="{{ route('courtesies.index', ['status' => 'pendiente']) }}"
               class="text-[11px] font-medium text-stock-primary hover:underline">
                Ver todas →
            </a>
        </div>
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-50">
                <tr>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operadora</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-center px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Cant.</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Tipo</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($pendingCourtesies as $c)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-2.5 text-[12px] text-gray-400 whitespace-nowrap">{{ $c->created_at->format('d/m H:i') }}</td>
                    <td class="px-5 py-2.5 font-medium text-gray-800">{{ $c->user?->name ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-gray-500">{{ $c->sede?->nombre ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-gray-700">{{ $c->product?->nombre ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-center font-semibold text-gray-900">{{ $c->quantity }}</td>
                    <td class="px-5 py-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ \App\Models\CourtesyTransaction::tipoColor($c->tipo) }}">
                            {{ \App\Models\CourtesyTransaction::tipoLabel($c->tipo) }}
                        </span>
                    </td>
                    <td class="px-5 py-2.5 text-right">
                        <a href="{{ route('courtesies.show', $c) }}"
                           class="text-[12px] font-semibold text-stock-primary hover:underline">Revisar →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endcan

    {{-- Movimientos de caja pendientes --}}
    @can('cash_movements.approve')
    @if($pendingCashMovements->count() > 0)
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                {{-- Banknote icon --}}
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h2 class="text-[13px] font-semibold text-gray-800">Movimientos de Caja</h2>
                <span class="text-[9px] font-bold px-1.5 py-[2px] rounded-full bg-amber-100 text-amber-700">{{ $pendingCashMovements->count() }}</span>
            </div>
            <a href="{{ route('cash-movements.index', ['status' => 'pendiente']) }}"
               class="text-[11px] font-medium text-stock-primary hover:underline">
                Ver todos →
            </a>
        </div>
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-50">
                <tr>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operadora</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Tipo</th>
                    <th class="text-right px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Monto</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Motivo</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($pendingCashMovements as $m)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-2.5 text-[12px] text-gray-400 whitespace-nowrap">{{ $m->created_at->format('d/m H:i') }}</td>
                    <td class="px-5 py-2.5 font-medium text-gray-800">{{ $m->user?->name ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-gray-500">{{ $m->sede?->nombre ?? '—' }}</td>
                    <td class="px-5 py-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ \App\Models\CashMovement::typeColor($m->type) }}">
                            {{ \App\Models\CashMovement::typeLabel($m->type) }}
                        </span>
                    </td>
                    <td class="px-5 py-2.5 text-right font-semibold text-gray-900">${{ number_format($m->amount, 2) }}</td>
                    <td class="px-5 py-2.5 text-gray-600 max-w-[140px] truncate">{{ $m->motivo }}</td>
                    <td class="px-5 py-2.5 text-right">
                        <a href="{{ route('cash-movements.show', $m) }}"
                           class="text-[12px] font-semibold text-stock-primary hover:underline">Revisar →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endcan

</div>
@endsection
