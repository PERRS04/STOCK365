<div>
    {{-- Filters bar --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex flex-wrap items-center gap-4">
            {{-- Sede picker --}}
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Sede:</label>
                <select
                    wire:model.live="sedeId"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-stock-primary"
                >
                    @foreach($this->sedes as $sede)
                        <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar producto o SKU…"
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-stock-primary"
                />
                <div wire:loading.delay class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="w-4 h-4 text-stock-primary animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </div>
            </div>

            @if($search)
                <button wire:click="$set('search', '')" class="text-xs text-gray-500 hover:text-gray-700 transition">
                    Limpiar ×
                </button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto transition-opacity duration-150" wire:loading.class.delay="opacity-60">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">SKU</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Producto</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Marca</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Stock Actual</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Stock Mínimo</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($this->inventories as $item)
                    <tr class="hover:bg-gray-50 transition {{ $item->isLowStock() ? 'bg-red-50 hover:bg-red-50' : '' }}">
                        <td class="py-3 px-4 font-mono text-xs text-gray-500">{{ $item->product->sku }}</td>
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $item->product->nombre }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $item->product->marca }}</td>
                        <td class="py-3 px-4 text-center font-bold {{ $item->isLowStock() ? 'text-red-600' : 'text-green-600' }}">
                            {{ $item->cantidad_stock }}
                        </td>
                        <td class="py-3 px-4 text-center text-gray-500">{{ $item->product->stock_minimo }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($item->isLowStock())
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Stock Bajo</span>
                            @else
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">OK</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-empty-state
                        :colspan="6"
                        icon="box"
                        :title="$search ? 'Sin resultados para &quot;' . $search . '&quot;' : 'Sin registros de inventario'"
                        :description="$search ? 'Intenta con otro término' : 'No hay stock registrado para esta sede'"
                    />
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 border-t border-gray-200">
            {{ $this->inventories->links() }}
        </div>
    </div>
</div>
