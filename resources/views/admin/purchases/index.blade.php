@extends('layouts.app')
@section('title', 'Historial de Compras')
@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="page-title">Historial de Compras</h1>
        <div class="flex gap-3 text-[13px]">
            <div class="px-4 py-2 bg-white rounded-xl border border-gray-200 shadow-card">
                <span class="text-gray-400">Este mes:</span>
                <span class="font-bold text-gray-900 ml-1">${{ number_format($totalMes, 2) }}</span>
            </div>
            <div class="px-4 py-2 bg-white rounded-xl border border-gray-200 shadow-card">
                <span class="text-gray-400">Este año:</span>
                <span class="font-bold text-gray-900 ml-1">${{ number_format($totalAnio, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('purchases.index') }}"
          class="bg-white rounded-2xl border border-gray-200/60 shadow-card px-5 py-4 flex flex-wrap gap-3 items-end">

        <div>
            <label class="form-label">Proveedor</label>
            <select name="provider_id" class="input input-sm">
                <option value="">Todos</option>
                @foreach($providers as $p)
                    <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Sede</label>
            <select name="sede_id" class="input input-sm">
                <option value="">Todas</option>
                @foreach($sedes as $s)
                    <option value="{{ $s->id }}" {{ request('sede_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}"
                   class="input input-sm">
        </div>

        <div>
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}"
                   class="input input-sm">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Proveedor</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operador</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Productos</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Monto</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($purchases as $purchase)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap text-[12px]">{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        @if($purchase->provider)
                            <a href="{{ route('suppliers.show', $purchase->provider) }}" class="font-medium text-stock-primary hover:underline">{{ $purchase->provider->nombre }}</a>
                        @else
                            <span class="text-gray-700">{{ $purchase->supplier_name }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $purchase->sede?->nombre ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $purchase->user?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ $purchase->items->count() }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-900">${{ number_format($purchase->monto_pagado, 2) }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('inventory-receipts.show', $purchase) }}" class="text-[12px] text-stock-primary hover:underline">Ver →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-[13px] text-gray-400">Sin compras registradas con los filtros aplicados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $purchases->links() }}
        </div>
    </div>

</div>
@endsection
