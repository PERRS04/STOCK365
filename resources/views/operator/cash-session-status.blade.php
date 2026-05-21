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
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
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
                    <dt class="text-[13px] text-gray-500">Monto inicial</dt>
                    <dd class="text-[13px] font-semibold text-gray-800">{{ formatCurrency($session->opening_amount) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-[13px] text-gray-500">Apertura</dt>
                    <dd class="text-[13px] font-medium text-gray-800">{{ $session->opened_at->format('d/m/Y H:i') }}</dd>
                </div>
                @if($session->notes)
                <div class="flex justify-between">
                    <dt class="text-[13px] text-gray-500">Notas</dt>
                    <dd class="text-[13px] font-medium text-gray-800 text-right max-w-xs">{{ $session->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>

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
