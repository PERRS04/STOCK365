@extends('layouts.app')

@section('title', 'Abrir Caja')

@section('content')
<div class="max-w-md mx-auto">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-[18px] font-semibold text-gray-900">Apertura de caja</h1>
        <p class="text-[12px] text-gray-400 mt-0.5">{{ $sede->nombre }} · {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
            <ul class="list-disc list-inside text-red-600 text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Inherited amount display --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-[0_1px_6px_rgba(0,0,0,0.05)] p-7 mb-4">

        @if($lastClosing)
            <div class="flex items-center gap-1.5 mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span class="text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-600">Caja heredada del cierre anterior</span>
            </div>
            <p class="text-[42px] font-bold text-gray-900 leading-none tracking-tight mb-2">
                ${{ number_format($inheritedAmount, 2) }}
            </p>
            <p class="text-[12px] text-gray-400">
                Cierre del {{ $lastClosing->fecha_cierre->format('d/m/Y') }}
                @if($lastClosing->approvedBy)
                    · Aprobado por {{ $lastClosing->approvedBy->name }}
                @endif
            </p>
        @else
            <div class="flex items-center gap-1.5 mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                <span class="text-[10px] font-bold uppercase tracking-[0.12em] text-blue-500">Primera apertura de caja</span>
            </div>
            <p class="text-[42px] font-bold text-gray-900 leading-none tracking-tight mb-2">$0.00</p>
            <p class="text-[12px] text-gray-400">No hay cierres aprobados anteriores para esta sede.</p>
        @endif

    </div>

    {{-- Open form --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-[0_1px_6px_rgba(0,0,0,0.05)] px-7 py-6 mb-4">
        <form method="POST" action="{{ route('cash-session.store') }}">
            @csrf

            <div class="mb-6">
                <label class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                    Notas de apertura
                    <span class="text-gray-400 font-normal normal-case">(opcional)</span>
                </label>
                <textarea
                    name="notes"
                    rows="2"
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-[13px] text-gray-900
                           focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition resize-none"
                    placeholder="Observaciones al iniciar turno…"
                >{{ old('notes') }}</textarea>
            </div>

            <button
                type="submit"
                class="w-full bg-stock-primary hover:bg-blue-800 active:scale-[0.98] text-white font-bold text-[15px] py-3.5 rounded-xl transition
                       focus:outline-none focus:ring-2 focus:ring-stock-primary focus:ring-offset-2 flex items-center justify-center gap-2.5"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
                Abrir caja · ${{ number_format($inheritedAmount, 2) }}
            </button>
        </form>
    </div>

    {{-- Info note --}}
    <div class="flex items-start gap-2.5 px-4 py-3 rounded-xl bg-gray-50 border border-gray-100">
        <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-[12px] text-gray-500 leading-relaxed">
            El monto de apertura se hereda automáticamente del último cierre aprobado.
            Si necesitas ajustarlo, contacta a tu supervisor después de abrir la caja.
        </p>
    </div>

</div>
@endsection
