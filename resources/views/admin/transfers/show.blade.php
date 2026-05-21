@extends('layouts.app')
@section('title', 'Transferencia')
@section('content')

<div class="max-w-3xl space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('transfers.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Transferencias</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Transferencia #{{ $transfer->id }}</h1>
        @php
            $stateMap = [
                'pendiente' => ['bg-amber-50 text-amber-700 border-amber-200', 'Pendiente'],
                'aprobado'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Aprobado'],
                'rechazado' => ['bg-red-50 text-red-700 border-red-200', 'Rechazado'],
            ];
            [$stateCls, $stateLabel] = $stateMap[$transfer->estado] ?? ['bg-gray-100 text-gray-600 border-gray-200', $transfer->estado];
        @endphp
        <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[12px] font-medium {{ $stateCls }}">{{ $stateLabel }}</span>
    </div>

    {{-- Transfer summary --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5">
        <div class="grid grid-cols-3 gap-6">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Origen</p>
                <p class="text-[15px] font-semibold text-gray-900">{{ $transfer->fromSede->nombre }}</p>
            </div>
            <div class="text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Dirección</p>
                <p class="text-[15px] text-gray-500">→</p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Destino</p>
                <p class="text-[15px] font-semibold text-gray-900">{{ $transfer->toSede->nombre }}</p>
            </div>
        </div>

        @if($transfer->motivo)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Motivo</p>
            <p class="text-[13px] text-gray-700">{{ $transfer->motivo }}</p>
        </div>
        @endif

        <div class="mt-4 pt-4 border-t border-gray-100 flex gap-8 text-[12px] text-gray-500">
            <span>Solicitado por <strong class="text-gray-700">{{ $transfer->createdBy->name }}</strong></span>
            <span>{{ $transfer->created_at->format('d/m/Y H:i') }}</span>
            @if($transfer->approvedBy)
            <span>{{ $transfer->isApproved() ? 'Aprobado' : 'Rechazado' }} por <strong class="text-gray-700">{{ $transfer->approvedBy->name }}</strong> · {{ $transfer->aprobado_at->format('d/m/Y') }}</span>
            @endif
        </div>

        @if($transfer->notas_aprobacion)
        <div class="mt-3 px-4 py-3 {{ $transfer->isRejected() ? 'bg-red-50 border border-red-200 rounded-lg' : 'bg-gray-50 rounded-lg' }}">
            <p class="text-[12px] {{ $transfer->isRejected() ? 'text-red-700' : 'text-gray-600' }}">{{ $transfer->notas_aprobacion }}</p>
        </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100">
            <h2 class="text-[13px] font-semibold text-gray-900">Productos ({{ $transfer->items->count() }})</h2>
        </div>
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Cantidad</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($transfer->items as $item)
                <tr>
                    <td class="px-5 py-3 text-gray-800">
                        <p class="font-medium">{{ $item->product?->nombre ?? '—' }}</p>
                        <p class="text-[11px] text-gray-400">{{ $item->product?->marca }} {{ $item->product?->tamaño }}</p>
                    </td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ $item->cantidad }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Approval form (only if pending and has permission) --}}
    @if($transfer->isPending() && auth()->user()->can('products.create'))
    <div x-data="{ showReject: false }" class="space-y-3">

        {{-- Approve --}}
        <form method="POST" action="{{ route('transfers.approve', $transfer) }}"
              class="bg-white rounded-xl border border-emerald-200 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5 space-y-4"
              onsubmit="return confirm('¿Aprobar esta transferencia? El stock se actualizará en ambas sedes.')">
            @csrf
            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Notas de aprobación (opcional)</label>
                <textarea name="notas_aprobacion" rows="2" placeholder="Aprobado. Stock verificado."
                          class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400/20 focus:border-emerald-400 resize-none"></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="px-5 py-2 bg-emerald-600 text-white text-[13px] font-medium rounded-lg hover:bg-emerald-700 transition">
                    Aprobar Transferencia
                </button>
                <button type="button" @click="showReject = !showReject" class="text-[13px] text-red-500 hover:text-red-700 transition">
                    Rechazar
                </button>
            </div>
        </form>

        {{-- Reject --}}
        <form x-show="showReject" x-cloak method="POST" action="{{ route('transfers.reject', $transfer) }}"
              class="bg-red-50 rounded-xl border border-red-200 p-5 space-y-3">
            @csrf
            @method('PATCH')
            <label class="block text-[12px] font-medium text-red-700 mb-1.5">Motivo del rechazo *</label>
            <textarea name="notas_aprobacion" rows="2" required placeholder="Explica por qué se rechaza…"
                      class="w-full px-3 py-2 text-[13px] border border-red-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400/20 focus:border-red-400 resize-none bg-white"></textarea>
            <button type="submit" class="px-5 py-2 bg-red-600 text-white text-[13px] font-medium rounded-lg hover:bg-red-700 transition">
                Confirmar Rechazo
            </button>
        </form>

    </div>
    @endif

</div>
@endsection
