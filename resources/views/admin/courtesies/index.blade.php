@extends('layouts.app')
@section('title', 'Cortesías')
@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Dashboard</a>
            <span class="text-gray-200">/</span>
            <h1 class="text-[18px] font-semibold text-gray-900">Cortesías</h1>
        </div>
        <span class="text-[12px] text-gray-400">{{ $courtesies->total() }} registros</span>
    </div>

    {{-- Status tabs --}}
    <div class="flex gap-3">
        <a href="{{ route('courtesies.index', ['status' => 'pendiente']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $status === 'pendiente' ? 'bg-amber-50 border-amber-300 text-amber-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            Pendientes <span class="font-bold ml-1">{{ $pendingCount }}</span>
        </a>
        <a href="{{ route('courtesies.index', ['status' => 'aprobado']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $status === 'aprobado' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Aprobadas <span class="font-bold ml-1">{{ $approvedCount }}</span>
        </a>
        <a href="{{ route('courtesies.index', ['status' => 'rechazado']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $status === 'rechazado' ? 'bg-red-50 border-red-300 text-red-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-red-400"></span>
            Rechazadas <span class="font-bold ml-1">{{ $rejectedCount }}</span>
        </a>
        <a href="{{ route('courtesies.index', ['status' => 'all']) }}"
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $status === 'all' ? 'bg-gray-100 border-gray-300 text-gray-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            Todas
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('courtesies.index') }}" class="flex flex-wrap gap-3 items-end">
        <input type="hidden" name="status" value="{{ $status }}"/>

        <div>
            <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Sede</label>
            <select name="sede_id"
                    class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary bg-white">
                <option value="">Todas las sedes</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id }}" {{ request('sede_id') == $sede->id ? 'selected' : '' }}>{{ $sede->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Operadora</label>
            <select name="user_id"
                    class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary bg-white">
                <option value="">Todas</option>
                @foreach($operators as $op)
                    <option value="{{ $op->id }}" {{ request('user_id') == $op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary"/>
        </div>

        <div>
            <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary"/>
        </div>

        <button type="submit"
                class="px-3.5 py-1.5 bg-stock-primary text-white text-[12px] font-medium rounded-lg hover:bg-blue-800 transition-colors">
            Filtrar
        </button>
        @if(request()->hasAny(['sede_id','user_id','date_from','date_to']))
        <a href="{{ route('courtesies.index', ['status' => $status]) }}"
           class="px-3.5 py-1.5 text-[12px] text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg transition-colors">
            Limpiar
        </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operadora</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Cant.</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Tipo</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Foto</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Estado</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($courtesies as $courtesy)
                @php
                    $statusMap = [
                        'pendiente' => ['bg-amber-50 text-amber-700 border-amber-200', 'Pendiente'],
                        'aprobado'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Aprobado'],
                        'rechazado' => ['bg-red-50 text-red-700 border-red-200', 'Rechazado'],
                    ];
                    [$statusCls, $statusLabel] = $statusMap[$courtesy->status] ?? ['bg-gray-100 text-gray-600 border-gray-200', $courtesy->status];
                @endphp
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap text-[12px]">{{ $courtesy->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $courtesy->user?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $courtesy->sede?->nombre ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-700 max-w-[160px] truncate">{{ $courtesy->product?->nombre ?? '—' }}</td>
                    <td class="px-5 py-3 text-center font-semibold text-gray-900">{{ $courtesy->quantity }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ \App\Models\CourtesyTransaction::tipoColor($courtesy->tipo) }}">
                            {{ \App\Models\CourtesyTransaction::tipoLabel($courtesy->tipo) }}
                        </span>
                    </td>
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
                        <a href="{{ route('courtesies.show', $courtesy) }}"
                           class="text-[12px] font-medium text-stock-primary hover:underline">
                            {{ $courtesy->isPending() ? 'Revisar' : 'Ver' }} →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-10 text-center text-[13px] text-gray-400">
                        Sin cortesías {{ $status !== 'all' ? $status.'s' : '' }} registradas
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $courtesies->links() }}
        </div>
    </div>

</div>
@endsection
