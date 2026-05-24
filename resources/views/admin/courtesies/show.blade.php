@extends('layouts.app')
@section('title', 'Cortesía #' . $courtesy->id)
@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('courtesies.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Cortesías</a>
            <span class="text-gray-200">/</span>
            <h1 class="text-[18px] font-semibold text-gray-900">Cortesía #{{ $courtesy->id }}</h1>
        </div>
        @php
            $statusMap = [
                'pendiente' => ['bg-amber-50 text-amber-700 border-amber-200', 'Pendiente'],
                'aprobado'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Aprobado'],
                'rechazado' => ['bg-red-50 text-red-700 border-red-200', 'Rechazado'],
            ];
            [$statusCls, $statusLabel] = $statusMap[$courtesy->status] ?? ['bg-gray-100 text-gray-600 border-gray-200', $courtesy->status];
        @endphp
        <span class="inline-flex items-center px-3 py-1 rounded-full border text-[12px] font-semibold {{ $statusCls }}">{{ $statusLabel }}</span>
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- ── LEFT PANEL ──────────────────────────────────────────────────── --}}
        <div class="col-span-1 space-y-4">

            {{-- Datos --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">Datos de la cortesía</h2>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] text-gray-400 shrink-0">Producto</span>
                        <span class="text-[12px] font-semibold text-gray-800 text-right">{{ $courtesy->product?->nombre ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Cantidad</span>
                        <span class="text-[18px] font-bold text-gray-900">{{ $courtesy->quantity }} uds</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Tipo</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ \App\Models\CourtesyTransaction::tipoColor($courtesy->tipo) }}">
                            {{ \App\Models\CourtesyTransaction::tipoLabel($courtesy->tipo) }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] text-gray-400 shrink-0">Motivo</span>
                        <span class="text-[12px] text-gray-700 text-right">{{ $courtesy->motivo }}</span>
                    </div>
                    @if($courtesy->cliente_nombre)
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Cliente</span>
                        <span class="text-[12px] text-gray-700">{{ $courtesy->cliente_nombre }}</span>
                    </div>
                    @endif
                    @if($courtesy->observaciones)
                    <div class="pt-1 border-t border-gray-50">
                        <p class="text-[11px] text-gray-400 mb-1">Observaciones</p>
                        <p class="text-[12px] text-gray-600">{{ $courtesy->observaciones }}</p>
                    </div>
                    @endif
                    <div class="pt-2 border-t border-gray-50 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-400">Operadora</span>
                            <span class="text-[12px] font-medium text-gray-700">{{ $courtesy->user?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-400">Sede</span>
                            <span class="text-[12px] font-medium text-gray-700">{{ $courtesy->sede?->nombre ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-400">Registrada</span>
                            <span class="text-[12px] text-gray-500">{{ $courtesy->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Foto evidencia --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">Foto evidencia</h2>
                </div>
                <div class="p-3">
                    <a href="{{ Storage::url($courtesy->attachment_path) }}" target="_blank">
                        <img src="{{ Storage::url($courtesy->attachment_path) }}"
                             alt="Evidencia cortesía #{{ $courtesy->id }}"
                             class="w-full rounded-lg border border-gray-100 object-cover hover:opacity-90 transition-opacity"/>
                    </a>
                    <p class="text-[10px] text-gray-400 text-center mt-2">Toca para ver en tamaño completo</p>
                </div>
            </div>

            {{-- Resolución (si ya fue procesada) --}}
            @if(! $courtesy->isPending())
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">
                        {{ $courtesy->isApproved() ? 'Aprobación' : 'Rechazo' }}
                    </h2>
                </div>
                <div class="px-5 py-4 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Por</span>
                        <span class="text-[12px] font-medium text-gray-700">{{ $courtesy->approvedBy?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Fecha</span>
                        <span class="text-[12px] text-gray-500">{{ $courtesy->approved_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($courtesy->rejection_reason)
                    <div class="pt-2 border-t border-gray-50">
                        <p class="text-[11px] text-gray-400 mb-1">Motivo de rechazo</p>
                        <p class="text-[12px] text-red-600">{{ $courtesy->rejection_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- ── RIGHT PANEL ─────────────────────────────────────────────────── --}}
        <div class="col-span-2">

            @if($courtesy->isPending())

            {{-- Stock check --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden mb-5">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">Verificación de stock</h2>
                </div>
                <div class="p-5">
                    @if($inventory)
                        @php $stockOk = $inventory->cantidad_stock >= $courtesy->quantity; @endphp
                        <div class="flex items-center justify-between p-4 rounded-xl border
                                    {{ $stockOk ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }}">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.06em]
                                          {{ $stockOk ? 'text-emerald-600' : 'text-red-600' }}">
                                    Stock actual · {{ $courtesy->sede?->nombre }}
                                </p>
                                <p class="text-[28px] font-bold leading-tight mt-1
                                          {{ $stockOk ? 'text-emerald-700' : 'text-red-700' }}">
                                    {{ $inventory->cantidad_stock }} uds
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] text-gray-500 uppercase tracking-[0.06em]">Solicitado</p>
                                <p class="text-[28px] font-bold text-gray-700 leading-tight mt-1">{{ $courtesy->quantity }} uds</p>
                            </div>
                        </div>
                        @if(! $stockOk)
                        <p class="mt-3 text-[12px] text-red-600 font-medium">
                            ⚠ Stock insuficiente. Disponible: {{ $inventory->cantidad_stock }} — Solicitado: {{ $courtesy->quantity }}. Si aprueba, la operación fallará.
                        </p>
                        @endif
                    @else
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="text-[12px] text-amber-700 font-medium">Sin stock registrado para este producto en esta sede.</p>
                            <p class="text-[11px] text-amber-600 mt-1">No se puede verificar disponibilidad. Si aprueba, la operación fallará por stock insuficiente.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Approve / Reject --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden"
                 x-data="{ showReject: false }">

                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">Resolución</h2>
                </div>

                <div class="p-5 space-y-4">

                    {{-- Approve --}}
                    <form method="POST" action="{{ route('courtesies.approve', $courtesy) }}">
                        @csrf
                        <div class="flex items-center justify-between p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <div>
                                <p class="text-[13px] font-semibold text-emerald-800">Aprobar cortesía</p>
                                <p class="text-[11px] text-emerald-600 mt-0.5">Descuenta {{ $courtesy->quantity }} uds de stock y registra movimiento de inventario.</p>
                            </div>
                            <button type="submit"
                                    onclick="return confirm('¿Confirmar aprobación? Se descontarán {{ $courtesy->quantity }} unidades de {{ $courtesy->product?->nombre }}.')"
                                    class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[13px] font-semibold rounded-lg transition-colors shrink-0 ml-4">
                                Aprobar
                            </button>
                        </div>
                    </form>

                    {{-- Reject toggle --}}
                    <div>
                        <button @click="showReject = !showReject" type="button"
                                class="text-[12px] font-medium text-red-500 hover:text-red-700 transition-colors">
                            <span x-show="!showReject">↓ Rechazar cortesía</span>
                            <span x-show="showReject">↑ Cancelar rechazo</span>
                        </button>

                        <div x-show="showReject" x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                             x-cloak class="mt-3 p-4 border border-red-200 rounded-xl bg-red-50/50">
                            <form method="POST" action="{{ route('courtesies.reject', $courtesy) }}">
                                @csrf
                                @method('PATCH')
                                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-red-600 mb-1.5">
                                    Motivo del rechazo <span class="text-red-400">*</span>
                                </label>
                                <textarea name="rejection_reason" required rows="3"
                                          placeholder="Explica por qué se rechaza esta cortesía..."
                                          class="w-full px-3.5 py-2.5 text-[13px] border border-red-200 rounded-lg resize-none focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-300/30"></textarea>
                                <div class="mt-3 flex justify-end">
                                    <button type="submit"
                                            class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-[13px] font-semibold rounded-lg transition-colors">
                                        Confirmar rechazo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <p class="text-[11px] text-gray-400">
                        Al aprobar, se registrará un movimiento de inventario tipo "cortesía" con trazabilidad completa.
                    </p>
                </div>
            </div>

            @else

            {{-- Already processed --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-8 text-center">
                @if($courtesy->isApproved())
                    <svg class="w-12 h-12 text-emerald-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-[15px] font-semibold text-emerald-700">Cortesía aprobada</p>
                    <p class="text-[12px] text-gray-400 mt-1">Stock descontado y movimiento de inventario registrado.</p>
                @else
                    <svg class="w-12 h-12 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-[15px] font-semibold text-red-700">Cortesía rechazada</p>
                    <p class="text-[12px] text-gray-400 mt-1">No se realizó ningún cambio en el inventario.</p>
                @endif
            </div>

            @endif

        </div>

    </div>

</div>
@endsection
