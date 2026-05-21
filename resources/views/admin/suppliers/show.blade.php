@extends('layouts.app')
@section('title', $supplier->nombre)
@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('suppliers.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Proveedores</a>
            <span class="text-gray-200">/</span>
            <h1 class="text-[18px] font-semibold text-gray-900">{{ $supplier->nombre }}</h1>
            @if(!$supplier->activo)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium bg-gray-100 text-gray-500 border-gray-200">Inactivo</span>
            @endif
        </div>
        @can('products.create')
        <a href="{{ route('suppliers.edit', $supplier) }}"
           class="px-3.5 py-2 rounded-lg border border-gray-200 text-[13px] font-medium text-gray-700 hover:bg-gray-50 transition">
            Editar
        </a>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-4">

        {{-- KPIs --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Total Comprado</p>
            <p class="text-[22px] font-bold text-gray-900">${{ number_format($stats['total_pagado'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Recepciones Aprobadas</p>
            <p class="text-[22px] font-bold text-gray-900">{{ $stats['total_compras'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Última Compra</p>
            <p class="text-[16px] font-semibold text-gray-900">
                {{ $stats['ultima_compra'] ? \Carbon\Carbon::parse($stats['ultima_compra'])->format('d/m/Y') : '—' }}
            </p>
            @if($stats['pendientes'] > 0)
                <p class="text-[11px] text-amber-600 mt-0.5">{{ $stats['pendientes'] }} pendiente(s)</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- Info card --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5 space-y-4">
            <h2 class="text-[13px] font-semibold text-gray-900">Información</h2>

            @foreach([
                ['RUC / NIT', $supplier->ruc_nit],
                ['Contacto', $supplier->contacto_principal],
                ['Teléfono', $supplier->telefono],
                ['Email', $supplier->email],
                ['Dirección', $supplier->direccion],
            ] as [$label, $value])
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">{{ $label }}</p>
                <p class="text-[13px] text-gray-700 mt-0.5">{{ $value ?? '—' }}</p>
            </div>
            @endforeach

            @if($supplier->observaciones)
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Observaciones</p>
                <p class="text-[12px] text-gray-600 mt-0.5">{{ $supplier->observaciones }}</p>
            </div>
            @endif

            <div class="pt-2 border-t border-gray-100">
                <p class="text-[10px] text-gray-400">Registrado por {{ $supplier->createdBy->name ?? 'Sistema' }} · {{ $supplier->created_at->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- Historial compras --}}
        <div class="col-span-2 bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <h2 class="text-[13px] font-semibold text-gray-900">Historial de Recepciones</h2>
            </div>
            <table class="w-full text-[13px]">
                <thead class="border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                        <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                        <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operador</th>
                        <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Monto</th>
                        <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($receipts as $receipt)
                    @php
                        $stateMap = [
                            'pendiente' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'aprobado'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'rechazado' => 'bg-red-50 text-red-700 border-red-200',
                        ];
                        $stateLabel = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];
                    @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 text-gray-500 text-[12px]">{{ $receipt->created_at->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $receipt->sede?->nombre ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $receipt->user?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">${{ number_format($receipt->monto_pagado, 2) }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $stateMap[$receipt->estado] ?? '' }}">
                                {{ $stateLabel[$receipt->estado] ?? $receipt->estado }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('inventory-receipts.show', $receipt) }}" class="text-[12px] text-stock-primary hover:underline">Ver →</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-[13px] text-gray-400">Sin recepciones registradas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3.5 border-t border-gray-100">
                {{ $receipts->links() }}
            </div>
        </div>

    </div>

</div>
@endsection
