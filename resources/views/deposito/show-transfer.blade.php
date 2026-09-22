@extends('layouts.app')
@section('title', 'Transferencia #' . $transfer->id)
@section('content')

<div class="max-w-2xl space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('deposito.transferencias') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Transferencias</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Transferencia #{{ $transfer->id }}</h1>
        @php
            $stateMap = [
                'pendiente' => 'bg-amber-50 text-amber-700 border-amber-200',
                'aprobado'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'rechazado' => 'bg-red-50 text-red-700 border-red-200',
            ];
            $stateCls = $stateMap[$transfer->estado] ?? 'bg-gray-100 text-gray-600 border-gray-200';
        @endphp
        <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-medium {{ $stateCls }}">
            {{ ucfirst($transfer->estado) }}
        </span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5 space-y-4">
        <div class="grid grid-cols-2 gap-4 text-[13px]">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Origen</p>
                <p class="font-medium text-gray-900">{{ $transfer->fromLocationName() }}</p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Destino</p>
                <p class="font-medium text-gray-900">{{ $transfer->toLocationName() }}</p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Creado por</p>
                <p class="text-gray-700">{{ $transfer->createdBy?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Fecha</p>
                <p class="text-gray-700">{{ $transfer->created_at->format('d/m/Y H:i') }}</p>
            </div>
            @if($transfer->motivo)
            <div class="col-span-2">
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Motivo</p>
                <p class="text-gray-700">{{ $transfer->motivo }}</p>
            </div>
            @endif
            @if($transfer->notas_aprobacion)
            <div class="col-span-2">
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">
                    {{ $transfer->estado === 'rechazado' ? 'Motivo de rechazo' : 'Notas de aprobación' }}
                </p>
                <p class="text-gray-700">{{ $transfer->notas_aprobacion }}</p>
            </div>
            @endif
            @if($transfer->approvedBy)
            <div class="col-span-2">
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">
                    {{ $transfer->estado === 'aprobado' ? 'Aprobado por' : 'Rechazado por' }}
                </p>
                <p class="text-gray-700">{{ $transfer->approvedBy->name }} · {{ $transfer->aprobado_at?->format('d/m/Y H:i') }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100">
            <h2 class="text-[13px] font-semibold text-gray-900">Productos</h2>
        </div>
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Cantidad</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($transfer->items as $item)
                <tr>
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800">{{ $item->product?->nombre ?? '—' }}</p>
                        @if($item->product?->sku)
                            <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ $item->product->sku }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center font-bold text-gray-900">{{ $item->cantidad }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($transfer->isPending())
    <div class="px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-[13px] text-amber-700">
        Esta transferencia está pendiente de aprobación por un supervisor o boss. El stock no se modifica hasta ser aprobada.
    </div>
    @endif

</div>
@endsection
