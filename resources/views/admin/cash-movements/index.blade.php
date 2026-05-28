@extends('layouts.app')
@section('title', 'Movimientos de Caja')
@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Dashboard</a>
            <span class="text-gray-200">/</span>
            <h1 class="page-title">Movimientos de Caja</h1>
        </div>
        <span class="text-[12px] text-gray-400">{{ $movements->total() }} registros</span>
    </div>

    {{-- Status tabs --}}
    <div class="flex gap-3">
        <a href="{{ route('cash-movements.index', ['status' => 'pendiente']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $status === 'pendiente' ? 'bg-amber-50 border-amber-300 text-amber-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            Pendientes <span class="font-bold ml-1">{{ $pendingCount }}</span>
        </a>
        <a href="{{ route('cash-movements.index', ['status' => 'aprobado']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $status === 'aprobado' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Aprobados <span class="font-bold ml-1">{{ $approvedCount }}</span>
        </a>
        <a href="{{ route('cash-movements.index', ['status' => 'rechazado']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $status === 'rechazado' ? 'bg-red-50 border-red-300 text-red-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-red-400"></span>
            Rechazados <span class="font-bold ml-1">{{ $rejectedCount }}</span>
        </a>
        <a href="{{ route('cash-movements.index', ['status' => 'all']) }}"
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $status === 'all' ? 'bg-gray-100 border-gray-300 text-gray-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            Todos
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('cash-movements.index') }}" class="flex flex-wrap gap-3 items-end">
        <input type="hidden" name="status" value="{{ $status }}"/>

        <div>
            <label class="form-label">Sede</label>
            <select name="sede_id"
                    class="input input-sm">
                <option value="">Todas las sedes</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id }}" {{ request('sede_id') == $sede->id ? 'selected' : '' }}>{{ $sede->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Operadora</label>
            <select name="user_id"
                    class="input input-sm">
                <option value="">Todas</option>
                @foreach($operators as $op)
                    <option value="{{ $op->id }}" {{ request('user_id') == $op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="input input-sm"/>
        </div>

        <div>
            <label class="form-label">Hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="input input-sm"/>
        </div>

        <button type="submit" class="btn btn-primary btn-sm">
            Filtrar
        </button>
        @if(request()->hasAny(['sede_id','user_id','date_from','date_to']))
        <a href="{{ route('cash-movements.index', ['status' => $status]) }}" class="btn btn-ghost btn-sm">
            Limpiar
        </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operadora</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Tipo</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Monto</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 max-w-[160px]">Motivo</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Foto</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Estado</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($movements as $movement)
                @php
                    $statusMap = [
                        'pendiente' => ['bg-amber-50 text-amber-700 border-amber-200', 'Pendiente'],
                        'aprobado'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Aprobado'],
                        'rechazado' => ['bg-red-50 text-red-700 border-red-200', 'Rechazado'],
                    ];
                    [$statusCls, $statusLabel] = $statusMap[$movement->status] ?? ['bg-gray-100 text-gray-600 border-gray-200', $movement->status];
                @endphp
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap text-[12px]">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $movement->user?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $movement->sede?->nombre ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ \App\Models\CashMovement::typeColor($movement->type) }}">
                            {{ \App\Models\CashMovement::typeLabel($movement->type) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-900">${{ number_format($movement->amount, 2) }}</td>
                    <td class="px-5 py-3 text-gray-600 max-w-[160px] truncate">{{ $movement->motivo }}</td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 border border-emerald-200" title="Foto adjunta">
                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $statusCls }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('cash-movements.show', $movement) }}"
                           class="text-[12px] font-medium text-stock-primary hover:underline">
                            {{ $movement->isPending() ? 'Revisar' : 'Ver' }} →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-10 text-center text-[13px] text-gray-400">
                        Sin movimientos {{ $status !== 'all' ? $status.'s' : '' }} registrados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $movements->links() }}
        </div>
    </div>

</div>
@endsection
