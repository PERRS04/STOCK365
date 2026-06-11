<div>
    {{-- Search --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, SKU o marca…"
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

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto transition-opacity duration-150" wire:loading.class.delay="opacity-60">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">SKU</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nombre</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Marca</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Tamaño</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">P. Compra</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">P. Venta</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Stock Mín.</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($this->products as $product)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 font-mono text-xs text-gray-500">{{ $product->sku }}</td>
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $product->nombre }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $product->marca }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $product->tamaño }}</td>
                        <td class="py-3 px-4 text-right">{{ formatCurrency($product->precio_compra) }}</td>
                        <td class="py-3 px-4 text-right font-semibold text-stock-primary">{{ formatCurrency($product->precio_venta) }}</td>
                        <td class="py-3 px-4 text-center">{{ $product->stock_minimo }}</td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('products.edit', $product) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium transition text-xs">Editar</a>
                                <button
                                    type="button"
                                    @click="
                                        Swal.fire({
                                            title: '¿Desactivar producto?',
                                            text: '{{ addslashes($product->nombre) }}',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#dc2626',
                                            cancelButtonColor: '#6b7280',
                                            confirmButtonText: 'Desactivar',
                                            cancelButtonText: 'Cancelar',
                                            reverseButtons: true,
                                        }).then(r => { if (r.isConfirmed) $wire.deactivate({{ $product->id }}) })
                                    "
                                    class="text-red-600 hover:text-red-800 font-medium transition text-xs">
                                    Desactivar
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty-state
                        :colspan="8"
                        icon="box"
                        :title="$search ? 'Sin resultados para &quot;' . $search . '&quot;' : 'Sin productos registrados'"
                        :description="$search ? 'Intenta con otro término de búsqueda' : 'Crea el primer producto para comenzar'"
                    />
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 border-t border-gray-200">
            {{ $this->products->links() }}
        </div>
    </div>
</div>
