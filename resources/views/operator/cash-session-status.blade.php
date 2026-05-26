@extends('layouts.app')

@section('title', 'Estado de Caja')

@section('content')
<div class="max-w-xl mx-auto">

    <div class="mb-6">
        <h1 class="text-[18px] font-semibold text-gray-900">Mi caja</h1>
        <p class="text-[12px] text-gray-400 mt-0.5">{{ $sede->nombre }}</p>
    </div>

    @if(!$session)

        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] px-8 py-12 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-[15px] font-semibold text-gray-700 mb-1">Sin caja activa</h2>
            <p class="text-[13px] text-gray-400 mb-6">No tienes ninguna sesión de caja abierta en este momento.</p>
            <a href="{{ route('cash-session.create') }}"
               class="inline-flex items-center gap-2 bg-stock-primary hover:bg-blue-800 text-white font-semibold text-[13px] px-5 py-2.5 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Abrir caja
            </a>
        </div>

    @else

        {{-- Status banner --}}
        @if($session->isOpen())
            <div class="mb-5 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2.5">
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[13px] font-semibold text-emerald-700">Caja abierta</span>
                <span class="ml-auto text-[12px] text-emerald-600">Desde {{ $session->opened_at->format('H:i') }}</span>
            </div>
        @else
            <div class="mb-5 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="text-[13px] font-semibold text-amber-700">Cierre pendiente de aprobación</span>
            </div>
        @endif

        {{-- KPI strip --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] divide-x divide-gray-100 mb-5">
            <div class="grid grid-cols-3">
                <div class="px-5 py-4 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 mb-1">Ventas turno</p>
                    <p class="text-[18px] font-bold text-gray-900">{{ formatCurrency($session->total_sales) }}</p>
                </div>
                <div class="px-5 py-4 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 mb-1">Transacciones</p>
                    <p class="text-[18px] font-bold text-gray-900">{{ $session->sales_count }}</p>
                </div>
                <div class="px-5 py-4 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 mb-1">Tiempo abierta</p>
                    <p class="text-[18px] font-bold text-gray-900">{{ $session->duration }}</p>
                </div>
            </div>
        </div>

        {{-- Detail --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] px-5 py-4 mb-5">
            <h3 class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-3">Detalle de sesión</h3>
            <dl class="space-y-2.5">
                <div class="flex justify-between">
                    <dt class="text-[13px] text-gray-500">
                        Apertura
                        @if($session->inheritedFromClosing)
                            <span class="ml-1 text-[10px] text-emerald-600 font-medium bg-emerald-50 px-1.5 py-0.5 rounded">
                                heredada
                            </span>
                        @endif
                    </dt>
                    <dd class="text-[13px] font-semibold text-gray-800">{{ formatCurrency($session->opening_amount) }}</dd>
                </div>
                @if($session->inheritedFromClosing)
                <div class="flex justify-between">
                    <dt class="text-[13px] text-gray-500">Cierre origen</dt>
                    <dd class="text-[13px] text-gray-600">{{ $session->inheritedFromClosing->fecha_cierre->format('d/m/Y') }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-[13px] text-gray-500">Hora de apertura</dt>
                    <dd class="text-[13px] font-medium text-gray-800">{{ $session->opened_at->format('d/m/Y H:i') }}</dd>
                </div>
                @if($session->notes)
                <div class="flex justify-between">
                    <dt class="text-[13px] text-gray-500">Notas</dt>
                    <dd class="text-[13px] font-medium text-gray-800 text-right max-w-xs">{{ $session->notes }}</dd>
                </div>
                @endif
                @if($session->adjustments->isNotEmpty())
                <div class="pt-1 border-t border-gray-100">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-amber-500 mb-1.5">Ajustes aplicados</p>
                    @foreach($session->adjustments as $adj)
                    <div class="text-[12px] text-gray-500 flex justify-between items-start py-0.5">
                        <span class="truncate max-w-[200px]">{{ $adj->motivo }}</span>
                        <span class="font-medium text-gray-700 ml-2 shrink-0">
                            ${{ number_format($adj->monto_anterior, 2) }} → ${{ number_format($adj->monto_nuevo, 2) }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </dl>
        </div>

        {{-- Supervisor: adjust opening amount --}}
        @if(auth()->user()->isAdminLevel() && $session->isOpen())
        <div x-data="{ open: false }" class="bg-white rounded-xl border border-amber-200 shadow-[0_1px_4px_rgba(0,0,0,0.04)] mb-5 overflow-hidden">
            <button @click="open = !open" type="button"
                class="w-full flex items-center justify-between px-5 py-3.5 hover:bg-amber-50 transition">
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span class="text-[13px] font-semibold text-amber-700">Ajustar apertura de caja</span>
                </div>
                <svg class="w-4 h-4 text-amber-400 transition-transform" :class="open && 'rotate-180'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-5 pb-5 border-t border-amber-100 pt-4 space-y-3">
                <p class="text-[12px] text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
                    Solo supervisor/administrador · El ajuste queda auditado permanentemente.
                </p>
                <form action="{{ route('cash-sessions.adjust-opening', $session) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1">
                            Nuevo monto de apertura <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="number" name="monto_nuevo" step="0.01" min="0" required
                                   placeholder="{{ number_format($session->opening_amount, 2) }}"
                                   class="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-lg text-[15px] font-semibold
                                          focus:outline-none focus:ring-2 focus:ring-amber-400/30 focus:border-amber-400 transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1">
                            Motivo del ajuste <span class="text-red-500">*</span>
                        </label>
                        <textarea name="motivo" rows="2" required minlength="5" maxlength="500"
                                  placeholder="Explica la razón del ajuste…"
                                  class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-[13px]
                                         focus:outline-none focus:ring-2 focus:ring-amber-400/30 focus:border-amber-400 transition resize-none"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-[13px] rounded-lg transition">
                        Aplicar ajuste
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        @if($session->isOpen())
            <div class="flex gap-3">
                <a href="{{ route('pos.create') }}"
                   class="flex-1 bg-stock-primary hover:bg-blue-800 text-white font-semibold text-[13px] py-2.5 rounded-lg transition-colors text-center flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    Ir a ventas
                </a>
                <a href="{{ route('cash-closing.create') }}"
                   class="flex-1 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-[13px] py-2.5 rounded-lg transition-colors text-center flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Cerrar caja
                </a>
            </div>
        @else
            <div class="px-4 py-3.5 bg-amber-50 border border-amber-200 rounded-xl text-[13px] text-amber-700">
                Tu cierre de caja está pendiente de aprobación. Una vez aprobado podrás abrir una nueva sesión.
            </div>
        @endif

    @endif

</div>
@endsection
