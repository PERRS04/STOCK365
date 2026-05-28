@extends('layouts.app')
@section('title', 'Recepciones de Mercancía')
@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Inventario</a>
            <span class="text-gray-200">/</span>
            <h1 class="page-title">Recepciones de Mercancía</h1>
        </div>
        <span class="text-[12px] text-gray-400">{{ $receipts->total() }} registros</span>
    </div>

    {{-- Stat strip --}}
    <div class="flex gap-3">
        <a href="{{ route('inventory-receipts.index', ['estado' => 'pendiente']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $estado === 'pendiente' ? 'bg-amber-50 border-amber-300 text-amber-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            Pendientes <span class="font-bold ml-1">{{ $pendingCount }}</span>
        </a>
        <a href="{{ route('inventory-receipts.index', ['estado' => 'aprobado']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $estado === 'aprobado' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Aprobadas <span class="font-bold ml-1">{{ $approvedCount }}</span>
        </a>
        <a href="{{ route('inventory-receipts.index', ['estado' => 'rechazado']) }}"
           class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $estado === 'rechazado' ? 'bg-red-50 border-red-300 text-red-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-red-400"></span>
            Rechazadas <span class="font-bold ml-1">{{ $rejectedCount }}</span>
        </a>
        <a href="{{ route('inventory-receipts.index', ['estado' => 'all']) }}"
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition-all
                  {{ $estado === 'all' ? 'bg-gray-100 border-gray-300 text-gray-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            Todas
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operador</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Proveedor</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Monto</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Factura</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Estado</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($receipts as $receipt)
                @php
                    $stateMap = [
                        'pendiente' => ['bg-amber-50 text-amber-700 border-amber-200', 'Pendiente'],
                        'aprobado'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Aprobado'],
                        'rechazado' => ['bg-red-50 text-red-700 border-red-200', 'Rechazado'],
                    ];
                    [$stateCls, $stateLabel] = $stateMap[$receipt->estado] ?? ['bg-gray-100 text-gray-600 border-gray-200', $receipt->estado];
                @endphp
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap text-[12px]">{{ $receipt->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $receipt->user?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $receipt->sede?->nombre ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-700 max-w-[180px] truncate">{{ $receipt->supplier_name }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-900">${{ number_format($receipt->monto_pagado, 2) }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($receipt->invoice_path)
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 border border-emerald-200" title="Factura adjunta">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                            </span>
                        @else
                            <span class="text-gray-300 text-[11px]">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $stateCls }}">{{ $stateLabel }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('inventory-receipts.show', $receipt) }}"
                           class="text-[12px] font-medium text-stock-primary hover:underline">
                            {{ $receipt->isPending() ? 'Revisar' : 'Ver' }} →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-[13px] text-gray-400">
                        Sin recepciones {{ $estado !== 'all' ? $estado.'s' : '' }} registradas
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $receipts->appends(['estado' => $estado])->links() }}
        </div>
    </div>

</div>
@endsection
