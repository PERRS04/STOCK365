<div class="space-y-6">
    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" wire:model.live="startDate"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" wire:model.live="endDate"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-stock-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Sede</label>
                <select wire:model.live="sedeId"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-stock-primary">
                    <option value="">Todas las sedes</option>
                    @foreach($this->sedes as $sede)
                        <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                    @endforeach
                </select>
            </div>

            @if($startDate || $endDate || $sedeId)
                <button wire:click="clear"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm transition">
                    Limpiar
                </button>
            @endif

            <div wire:loading.delay class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="w-4 h-4 animate-spin text-stock-primary" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Actualizando…
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-3 gap-4" wire:loading.class.delay="opacity-60">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-stock-primary">
            <p class="text-sm text-gray-600">Total Ventas</p>
            <p class="text-2xl font-bold text-stock-primary">{{ formatCurrency($this->totals['total']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-600">Transacciones</p>
            <p class="text-2xl font-bold text-blue-500">{{ number_format($this->totals['count']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-600">Promedio por Venta</p>
            <p class="text-2xl font-bold text-green-500">{{ formatCurrency($this->totals['promedio']) }}</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto transition-opacity duration-150" wire:loading.class.delay="opacity-60">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Fecha</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Sede</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Items</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Descuento</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($this->sales as $sale)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-gray-500">{{ $sale->fecha_venta->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-4">{{ $sale->sede->nombre }}</td>
                        <td class="py-3 px-4 text-center">{{ $sale->items->count() }}</td>
                        <td class="py-3 px-4 text-right text-red-500">{{ formatCurrency(0 - ($sale->descuento ?? 0)) }}</td>
                        <td class="py-3 px-4 text-right font-bold text-stock-primary">{{ formatCurrency($sale->total_sistema) }}</td>
                    </tr>
                @empty
                    <x-empty-state
                        :colspan="5"
                        icon="chart"
                        title="Sin ventas en el período"
                        description="Ajusta los filtros de fecha para ver resultados"
                    />
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 border-t border-gray-200">
            {{ $this->sales->links() }}
        </div>
    </div>
</div>
