@extends('layouts.app')

@section('title', 'Abrir Caja')

@section('content')
<div class="max-w-lg mx-auto">

    <div class="mb-6">
        <h1 class="text-[18px] font-semibold text-gray-900">Apertura de caja</h1>
        <p class="text-[12px] text-gray-400 mt-0.5">{{ $sede->nombre }} · {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="mb-5 p-4 bg-blue-50 border border-blue-200/80 rounded-xl flex gap-3">
        <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-[12px] text-blue-700">
            <p class="font-semibold mb-0.5">Antes de operar debes abrir la caja</p>
            <p class="text-blue-600/80">Registra el efectivo inicial. Todas las ventas quedarán vinculadas a esta sesión.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] px-8 py-7">
        <form method="POST" action="{{ route('cash-session.store') }}">
            @csrf

            <div class="mb-5">
                <label class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                    Monto inicial en caja <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[14px] font-medium">$</span>
                    <input
                        type="number"
                        name="opening_amount"
                        min="0"
                        step="0.01"
                        value="{{ old('opening_amount', '0.00') }}"
                        class="w-full pl-8 pr-4 py-2.5 border border-gray-300 rounded-lg text-[15px] font-semibold text-gray-900
                               focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition
                               @error('opening_amount') border-red-400 @enderror"
                        placeholder="0.00"
                        autofocus
                        required
                    >
                </div>
                @error('opening_amount')
                    <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-[11px] text-gray-400">Cuenta el efectivo físico que hay en la caja al abrir.</p>
            </div>

            <div class="mb-7">
                <label class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                    Notas de apertura <span class="text-gray-400 font-normal normal-case">(opcional)</span>
                </label>
                <textarea
                    name="notes"
                    rows="2"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-[13px] text-gray-900
                           focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition resize-none"
                    placeholder="Observaciones al abrir…"
                >{{ old('notes') }}</textarea>
            </div>

            <button
                type="submit"
                class="w-full bg-stock-primary hover:bg-blue-800 active:bg-blue-900 text-white font-semibold text-[14px] py-2.5 rounded-lg transition-colors
                       focus:outline-none focus:ring-2 focus:ring-stock-primary focus:ring-offset-2 flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
                Abrir caja y comenzar
            </button>
        </form>
    </div>

</div>
@endsection
