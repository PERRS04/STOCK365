@extends('layouts.app')
@section('title', 'Movimiento #' . $movement->id)
@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('cash-movements.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Movimientos</a>
            <span class="text-gray-200">/</span>
            <h1 class="text-[18px] font-semibold text-gray-900">Movimiento #{{ $movement->id }}</h1>
        </div>
        @php
            $statusMap = [
                'pendiente' => ['bg-amber-50 text-amber-700 border-amber-200', 'Pendiente'],
                'aprobado'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Aprobado'],
                'rechazado' => ['bg-red-50 text-red-700 border-red-200', 'Rechazado'],
            ];
            [$statusCls, $statusLabel] = $statusMap[$movement->status] ?? ['bg-gray-100 text-gray-600 border-gray-200', $movement->status];
        @endphp
        <span class="inline-flex items-center px-3 py-1 rounded-full border text-[12px] font-semibold {{ $statusCls }}">{{ $statusLabel }}</span>
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- ── LEFT PANEL ──────────────────────────────────────────────────── --}}
        <div class="col-span-1 space-y-4">

            {{-- Datos --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">Datos del movimiento</h2>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Tipo</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ \App\Models\CashMovement::typeColor($movement->type) }}">
                            {{ \App\Models\CashMovement::typeLabel($movement->type) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Monto</span>
                        <span class="text-[22px] font-bold text-gray-900">${{ number_format($movement->amount, 2) }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] text-gray-400 shrink-0">Motivo</span>
                        <span class="text-[12px] text-gray-700 text-right">{{ $movement->motivo }}</span>
                    </div>
                    @if($movement->observaciones)
                    <div class="pt-1 border-t border-gray-50">
                        <p class="text-[11px] text-gray-400 mb-1">Observaciones</p>
                        <p class="text-[12px] text-gray-600">{{ $movement->observaciones }}</p>
                    </div>
                    @endif
                    <div class="pt-2 border-t border-gray-50 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-400">Operadora</span>
                            <span class="text-[12px] font-medium text-gray-700">{{ $movement->user?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-400">Sede</span>
                            <span class="text-[12px] font-medium text-gray-700">{{ $movement->sede?->nombre ?? '—' }}</span>
                        </div>
                        @if($movement->cashSession)
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-400">Sesión caja</span>
                            <span class="text-[12px] text-gray-600">#{{ $movement->cashSession->id }} · {{ $movement->cashSession->opened_at->format('d/m H:i') }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-400">Registrado</span>
                            <span class="text-[12px] text-gray-500">{{ $movement->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Foto comprobante --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">Foto comprobante</h2>
                </div>
                <div class="p-3">
                    <a href="{{ Storage::url($movement->attachment_path) }}" target="_blank">
                        <img src="{{ Storage::url($movement->attachment_path) }}"
                             alt="Comprobante movimiento #{{ $movement->id }}"
                             class="w-full rounded-lg border border-gray-100 object-cover hover:opacity-90 transition-opacity"/>
                    </a>
                    <p class="text-[10px] text-gray-400 text-center mt-2">Toca para ver en tamaño completo</p>
                </div>
            </div>

            {{-- Resolución (si procesada) --}}
            @if(! $movement->isPending())
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">
                        {{ $movement->isApproved() ? 'Aprobación' : 'Rechazo' }}
                    </h2>
                </div>
                <div class="px-5 py-4 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Por</span>
                        <span class="text-[12px] font-medium text-gray-700">{{ $movement->approvedBy?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-400">Fecha</span>
                        <span class="text-[12px] text-gray-500">{{ $movement->approved_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($movement->rejection_reason)
                    <div class="pt-2 border-t border-gray-50">
                        <p class="text-[11px] text-gray-400 mb-1">Motivo de rechazo</p>
                        <p class="text-[12px] text-red-600">{{ $movement->rejection_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- ── RIGHT PANEL ─────────────────────────────────────────────────── --}}
        <div class="col-span-2">

            @if($movement->isPending())

            {{-- Resumen del movimiento --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden mb-5">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[12px] font-semibold text-gray-700 uppercase tracking-[0.06em]">Resumen</h2>
                </div>
                <div class="p-5">
                    <div class="flex items-center justify-between p-4 rounded-xl border
                                {{ $movement->isCredit() ? 'bg-emerald-50 border-emerald-200' : ($movement->isDebit() ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200') }}">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.06em]
                                      {{ $movement->isCredit() ? 'text-emerald-600' : ($movement->isDebit() ? 'text-red-600' : 'text-gray-500') }}">
                                {{ $movement->isCredit() ? 'Entrada de efectivo' : ($movement->isDebit() ? 'Salida de efectivo' : 'Ajuste / Neutral') }}
                            </p>
                            <p class="text-[12px] mt-1 {{ $movement->isCredit() ? 'text-emerald-700' : ($movement->isDebit() ? 'text-red-700' : 'text-gray-600') }}">
                                {{ \App\Models\CashMovement::typeLabel($movement->type) }}
                            </p>
                        </div>
                        <p class="text-[32px] font-bold
                                  {{ $movement->isCredit() ? 'text-emerald-700' : ($movement->isDebit() ? 'text-red-700' : 'text-gray-700') }}">
                            ${{ number_format($movement->amount, 2) }}
                        </p>
                    </div>
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
                    <form method="POST" action="{{ route('cash-movements.approve', $movement) }}">
                        @csrf
                        <div class="flex items-center justify-between p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <div>
                                <p class="text-[13px] font-semibold text-emerald-800">Aprobar movimiento</p>
                                <p class="text-[11px] text-emerald-600 mt-0.5">
                                    Confirma el {{ \App\Models\CashMovement::typeLabel($movement->type) }} de ${{ number_format($movement->amount, 2) }} en caja.
                                </p>
                            </div>
                            <button type="submit"
                                    onclick="return confirm('¿Confirmar aprobación del movimiento por ${{ number_format($movement->amount, 2) }}?')"
                                    class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[13px] font-semibold rounded-lg transition-colors shrink-0 ml-4">
                                Aprobar
                            </button>
                        </div>
                    </form>

                    {{-- Reject toggle --}}
                    <div>
                        <button @click="showReject = !showReject" type="button"
                                class="text-[12px] font-medium text-red-500 hover:text-red-700 transition-colors">
                            <span x-show="!showReject">↓ Rechazar movimiento</span>
                            <span x-show="showReject">↑ Cancelar rechazo</span>
                        </button>

                        <div x-show="showReject" x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                             x-cloak class="mt-3 p-4 border border-red-200 rounded-xl bg-red-50/50">
                            <form method="POST" action="{{ route('cash-movements.reject', $movement) }}">
                                @csrf
                                @method('PATCH')
                                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-red-600 mb-1.5">
                                    Motivo del rechazo <span class="text-red-400">*</span>
                                </label>
                                <textarea name="rejection_reason" required rows="3"
                                          placeholder="Explica por qué se rechaza este movimiento..."
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
                        Todos los movimientos quedan registrados en auditoría con trazabilidad completa de operadora, sede y supervisor.
                    </p>
                </div>
            </div>

            @else

            {{-- Already processed --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-8 text-center">
                @if($movement->isApproved())
                    <svg class="w-12 h-12 text-emerald-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-[15px] font-semibold text-emerald-700">Movimiento aprobado</p>
                    <p class="text-[12px] text-gray-400 mt-1">El movimiento fue validado y queda registrado en auditoría.</p>
                @else
                    <svg class="w-12 h-12 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-[15px] font-semibold text-red-700">Movimiento rechazado</p>
                    <p class="text-[12px] text-gray-400 mt-1">El movimiento no fue procesado.</p>
                @endif
            </div>

            @endif

        </div>

    </div>

</div>
@endsection
