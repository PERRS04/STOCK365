@extends('layouts.app')

@section('title', 'Cierre de Caja')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-[18px] font-semibold text-gray-900">Cierre de caja</h1>
            <p class="text-[12px] text-gray-400 mt-0.5">{{ $sede->nombre }} · {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Duplicate warning --}}
    @isset($existingClosing)
    <div class="p-4 bg-yellow-50 border border-yellow-300 rounded-lg flex items-start gap-3">
        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-yellow-800">Ya existe un cierre para hoy</p>
            <p class="text-xs text-yellow-700 mt-0.5">Estado: <strong>{{ ucfirst($existingClosing->estado) }}</strong> — registrado a las {{ $existingClosing->fecha_cierre->format('H:i') }}</p>
        </div>
    </div>
    @endisset

    {{-- Sales summary --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-6">
        <h3 class="font-semibold text-gray-700 mb-4">Ventas del Día</h3>
        @if($sales->count() > 0)
            <div class="overflow-x-auto mb-4 max-h-48 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 sticky top-0 bg-white">
                        <tr>
                            <th class="text-left py-2 font-semibold text-gray-600">Hora</th>
                            <th class="text-center py-2 font-semibold text-gray-600">Items</th>
                            <th class="text-right py-2 font-semibold text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="py-2 text-gray-600">{{ $sale->fecha_venta->format('H:i') }}</td>
                                <td class="py-2 text-center text-gray-600">{{ $sale->items->count() }}</td>
                                <td class="py-2 text-right font-medium">{{ formatCurrency($sale->total_sistema) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state icon="cart" title="Sin ventas hoy" description="No hay ventas registradas para este día"/>
        @endif

        <div class="flex justify-between items-center py-3 border-t border-gray-200">
            <span class="font-semibold text-gray-700">Total del Sistema:</span>
            <span class="text-2xl font-bold text-stock-primary">{{ formatCurrency($totalSistema) }}</span>
        </div>
    </div>

    {{-- Closing form --}}
    <div class="bg-white rounded-lg shadow p-6" x-data="cashClosing({{ $totalSistema }})">
        <h3 class="font-semibold text-gray-700 mb-4">Conteo de Dinero</h3>

        <form action="{{ route('cash-closing.store') }}" method="POST" class="space-y-4"
              data-confirm="¿Confirmar el cierre de caja del día?">
            @csrf

            @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-600 text-sm">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto Inicial (apertura) *</label>
                    <input type="number" step="0.01" name="monto_inicial" x-model="monto_inicial" min="0" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary"
                        placeholder="0.00">
                    <p class="text-xs text-gray-400 mt-0.5">Efectivo en caja al abrir</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Efectivo Contado *</label>
                    <input type="number" step="0.01" name="efectivo" x-model="efectivo" min="0" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary text-lg font-semibold"
                        placeholder="0.00">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transferencias (USD)</label>
                    <input type="number" step="0.01" name="transferencias" x-model="transferencias" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary"
                        placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cheques (USD)</label>
                    <input type="number" step="0.01" name="cheques" x-model="cheques" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary"
                        placeholder="0.00">
                </div>
            </div>

            {{-- Live comparison --}}
            <div class="p-4 rounded-lg border-2 transition-colors duration-200"
                 :class="Math.abs(diferencia) < 0.01 ? 'bg-green-50 border-green-300' : (diferencia > 0 ? 'bg-blue-50 border-blue-300' : 'bg-red-50 border-red-300')">
                <div class="grid grid-cols-3 gap-3 text-sm mb-3">
                    <div class="text-center">
                        <p class="text-gray-500 text-xs mb-1">Total Sistema</p>
                        <p class="font-bold text-gray-800">{{ formatCurrency($totalSistema) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-500 text-xs mb-1">Total Reportado</p>
                        <p class="font-bold text-gray-800" x-text="formatCurrency(totalReportado)"></p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-500 text-xs mb-1">Diferencia</p>
                        <p class="font-bold text-lg"
                           :class="Math.abs(diferencia) < 0.01 ? 'text-green-700' : (diferencia > 0 ? 'text-blue-700' : 'text-red-700')"
                           x-text="formatCurrency(diferencia)"></p>
                    </div>
                </div>
                <p class="text-xs text-center"
                   :class="Math.abs(diferencia) < 0.01 ? 'text-green-600' : (diferencia > 0 ? 'text-blue-600' : 'text-red-600')"
                   x-text="Math.abs(diferencia) < 0.01 ? 'Cuadrado exacto' : (diferencia > 0 ? 'Sobrante' : 'Faltante')">
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" maxlength="500"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-stock-primary resize-none"
                    placeholder="Notas adicionales sobre el cierre…"></textarea>
            </div>

            <div class="flex space-x-3 pt-2">
                <a href="{{ route('dashboard') }}" class="flex-1 py-2 text-center border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-sm">
                    Cancelar
                </a>
                <button type="submit"
                    class="flex-1 py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition focus:outline-none focus:ring-2 focus:ring-green-500">
                    Registrar Cierre
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function cashClosing(totalSistema) {
    return {
        monto_inicial: 0,
        efectivo: 0,
        transferencias: 0,
        cheques: 0,
        totalSistema: totalSistema,
        get totalReportado() {
            return (parseFloat(this.efectivo) || 0)
                 + (parseFloat(this.transferencias) || 0)
                 + (parseFloat(this.cheques) || 0);
        },
        get diferencia() {
            return this.totalReportado - this.totalSistema;
        },
    };
}
</script>
@endsection
