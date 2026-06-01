@extends('layouts.app')

@section('title', 'Cierre de Caja')

@section('content')
<div class="max-w-xl mx-auto space-y-5" x-data="cashClosing({{ (float) $totalSistema }})">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-[18px] font-semibold text-gray-900">Cierre de caja</h1>
            <p class="text-[12px] text-gray-400 mt-0.5">{{ $sede->nombre }} · {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
            <ul class="list-disc list-inside text-red-600 text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @isset($existingClosing)
    <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="text-[13px] font-semibold text-amber-800">Ya existe un cierre para hoy</p>
            <p class="text-[12px] text-amber-700 mt-0.5">Estado: <strong>{{ ucfirst($existingClosing->estado) }}</strong> — registrado a las {{ $existingClosing->fecha_cierre->format('H:i') }}</p>
        </div>
    </div>
    @endisset

    {{-- Live balance breakdown --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-[0_1px_6px_rgba(0,0,0,0.05)] overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-gray-400">Caja esperada</p>
        </div>

        <div class="px-5 divide-y divide-gray-50">

            @if($snapshot)

                {{-- Apertura heredada --}}
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-[13px] text-gray-500">Apertura heredada</span>
                    </div>
                    <span class="text-[13px] font-medium text-gray-700">
                        +${{ number_format($snapshot['opening'], 2) }}
                    </span>
                </div>

                {{-- Ventas efectivo --}}
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="text-[13px] text-gray-500">Ventas efectivo</span>
                    </div>
                    <span class="text-[13px] font-semibold text-emerald-600">
                        +${{ number_format($snapshot['ventas_efectivo'], 2) }}
                    </span>
                </div>

                {{-- Ingresos --}}
                @if($snapshot['ingresos'] > 0)
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-teal-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="text-[13px] text-gray-500">Fondos recibidos</span>
                    </div>
                    <span class="text-[13px] font-semibold text-teal-600">
                        +${{ number_format($snapshot['ingresos'], 2) }}
                    </span>
                </div>
                @endif

                {{-- Egresos --}}
                @if($snapshot['egresos'] > 0)
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <span class="text-[13px] text-gray-500">Depósitos / Egresos</span>
                    </div>
                    <span class="text-[13px] font-semibold text-amber-600">
                        -${{ number_format($snapshot['egresos'], 2) }}
                    </span>
                </div>
                @endif

                {{-- Cortesías --}}
                @if($snapshot['valor_cortesias'] > 0)
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-pink-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        </svg>
                        <span class="text-[13px] text-gray-500">Cortesías aprobadas</span>
                    </div>
                    <span class="text-[13px] font-semibold text-pink-500">
                        -${{ number_format($snapshot['valor_cortesias'], 2) }}
                    </span>
                </div>
                @endif

            @else

                {{-- Fallback: no session --}}
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="text-[13px] text-gray-500">Total ventas del día</span>
                    </div>
                    <span class="text-[13px] font-semibold text-emerald-600">
                        ${{ number_format($totalSistema, 2) }}
                    </span>
                </div>

            @endif

        </div>

        {{-- Total esperado --}}
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <span class="text-[12px] font-bold uppercase tracking-[0.08em] text-gray-500">Total esperado en caja</span>
            <span class="text-[20px] font-bold text-gray-900">${{ number_format($totalSistema, 2) }}</span>
        </div>
    </div>

    {{-- Closing form --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-[0_1px_6px_rgba(0,0,0,0.05)] overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-gray-400">Conteo físico</p>
        </div>

        <form action="{{ route('cash-closing.store') }}" method="POST" class="px-5 py-5 space-y-4">
            @csrf

            {{-- Efectivo (primary) --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                    Efectivo contado <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[15px] font-semibold pointer-events-none">$</span>
                    <input
                        type="number"
                        name="efectivo"
                        x-model="efectivo"
                        step="0.01"
                        min="0"
                        required
                        placeholder="0.00"
                        autofocus
                        class="w-full pl-8 pr-4 py-3.5 border border-gray-200 rounded-xl text-[20px] font-bold text-gray-900
                               focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition"
                    >
                </div>
                <p class="mt-1 text-[11px] text-gray-400">Dinero físico que queda en la caja al cerrar.</p>
            </div>

            {{-- Transferencias --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                    Transferencias
                    <span class="text-gray-400 font-normal normal-case">(opcional)</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[14px] pointer-events-none">$</span>
                    <input
                        type="number"
                        name="transferencias"
                        x-model="transferencias"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-xl text-[14px] text-gray-900
                               focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition"
                    >
                </div>
            </div>

            {{-- Cheques (collapsed by default) --}}
            <div x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="flex items-center gap-1.5 text-[11px] text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    Agregar cheques
                </button>
                <div x-show="open" x-transition class="mt-2">
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[14px] pointer-events-none">$</span>
                        <input
                            type="number"
                            name="cheques"
                            x-model="cheques"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-xl text-[14px] text-gray-900
                                   focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition"
                        >
                    </div>
                </div>
            </div>

            {{-- Observaciones --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                    Observaciones
                    <span class="text-gray-400 font-normal normal-case">(opcional)</span>
                </label>
                <textarea
                    name="observaciones"
                    rows="2"
                    maxlength="500"
                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-[13px] text-gray-900
                           focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition resize-none"
                    placeholder="Notas adicionales sobre el cierre…"></textarea>
            </div>

            {{-- Live comparison --}}
            <div class="rounded-xl border-2 p-4 transition-all duration-200"
                 :class="
                    Math.abs(diferencia) < 0.01
                        ? 'bg-emerald-50 border-emerald-300'
                        : (diferencia > 0 ? 'bg-blue-50 border-blue-300' : 'bg-red-50 border-red-300')
                 ">

                <div class="grid grid-cols-3 gap-4 mb-3">
                    <div class="text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 mb-1">Caja Esperada</p>
                        <p class="text-[16px] font-bold text-gray-800">${{ number_format($totalSistema, 2) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 mb-1">Total Reportado</p>
                        <p class="text-[16px] font-bold text-gray-800" x-text="'$' + totalReportado.toFixed(2)"></p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 mb-1">Diferencia</p>
                        <p class="text-[18px] font-bold"
                           :class="Math.abs(diferencia) < 0.01 ? 'text-emerald-700' : (diferencia > 0 ? 'text-blue-700' : 'text-red-700')"
                           x-text="(diferencia >= 0 ? '+' : '') + '$' + diferencia.toFixed(2)"></p>
                    </div>
                </div>

                <p class="text-[11px] font-semibold text-center"
                   :class="Math.abs(diferencia) < 0.01 ? 'text-emerald-600' : (diferencia > 0 ? 'text-blue-600' : 'text-red-600')"
                   x-text="Math.abs(diferencia) < 0.01 ? 'Cuadrado exacto ✓' : (diferencia > 0 ? 'Sobrante' : 'Faltante')">
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-1">
                <a href="{{ route('dashboard') }}"
                   class="flex-none py-2.5 px-5 border border-gray-200 rounded-xl text-[13px] font-medium text-gray-600 hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white rounded-xl font-bold text-[14px] transition
                               focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    Registrar Cierre
                </button>
            </div>
        </form>
    </div>

    {{-- Sales detail (collapsible) --}}
    @if($sales->count() > 0)
    <div x-data="{ open: false }"
         class="bg-white rounded-2xl border border-gray-200/80 shadow-[0_1px_6px_rgba(0,0,0,0.05)] overflow-hidden">
        <button @click="open = !open" type="button"
                class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition">
            <div class="flex items-center gap-2">
                <span class="text-[12px] font-semibold text-gray-600">
                    Detalle de ventas del día
                </span>
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">
                    {{ $sales->count() }} ventas
                </span>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-transition class="border-t border-gray-100">
            <div class="overflow-x-auto max-h-52 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 sticky top-0 bg-white">
                        <tr>
                            <th class="text-left py-2.5 px-5 text-[11px] font-semibold text-gray-500 uppercase tracking-[0.06em]">Hora</th>
                            <th class="text-center py-2.5 px-3 text-[11px] font-semibold text-gray-500 uppercase tracking-[0.06em]">Items</th>
                            <th class="text-right py-2.5 px-5 text-[11px] font-semibold text-gray-500 uppercase tracking-[0.06em]">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($sales as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5 px-5 text-[13px] text-gray-600">{{ $sale->fecha_venta->format('H:i') }}</td>
                                <td class="py-2.5 px-3 text-center text-[13px] text-gray-500">{{ $sale->items->count() }}</td>
                                <td class="py-2.5 px-5 text-right text-[13px] font-medium text-gray-800">{{ formatCurrency($sale->total_sistema) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
function cashClosing(cajaEsperada) {
    return {
        efectivo: 0,
        transferencias: 0,
        cheques: 0,
        get totalReportado() {
            return (parseFloat(this.efectivo) || 0)
                 + (parseFloat(this.transferencias) || 0)
                 + (parseFloat(this.cheques) || 0);
        },
        get diferencia() {
            return this.totalReportado - cajaEsperada;
        },
    };
}
</script>
@endsection
