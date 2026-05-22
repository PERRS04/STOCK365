<div>
    {{-- Search Input --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-4 mb-4">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input
                id="pos-search"
                type="text"
                wire:model.live.debounce.200ms="search"
                @keydown.enter.prevent="$wire.selectFirst()"
                placeholder="Buscar por nombre, SKU o marca — Enter añade el primero"
                autocomplete="off"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary text-sm"
            />
            <div wire:loading.delay class="absolute right-3 top-1/2 -translate-y-1/2">
                <svg class="w-4 h-4 text-stock-primary animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Product Grid --}}
    <div
        class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[420px] overflow-y-auto transition-opacity duration-150"
        wire:loading.class.delay="opacity-50"
    >
        @forelse($this->products as $product)
        @php
            $stock = $product->stock_sede;
            $sinStock = $stock !== null && $stock === 0;
            $stockBajo = $stock !== null && $stock > 0 && $stock < $product->stock_minimo;
        @endphp
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('add-to-cart', { detail: { id: {{ $product->id }}, nombre: {{ json_encode($product->nombre) }}, precio: {{ (float)$product->precio_venta }} } }))"
                @if($sinStock) title="Sin stock disponible" @endif
                class="relative bg-white border-2 rounded-lg p-3 text-left transition group
                       {{ $sinStock
                            ? 'border-gray-100 opacity-50 cursor-not-allowed'
                            : 'border-gray-200 hover:border-stock-accent focus:border-stock-accent focus:outline-none' }}"
            >
                {{-- Stock badge --}}
                @if($stock !== null)
                    <span class="absolute top-2 right-2 text-[9px] font-bold px-1.5 py-0.5 rounded-full
                        {{ $sinStock  ? 'bg-red-100 text-red-600'
                          : ($stockBajo ? 'bg-amber-100 text-amber-700'
                          :               'bg-emerald-50 text-emerald-700') }}">
                        {{ $sinStock ? 'Sin stock' : ($stockBajo ? 'Bajo: '.$stock : $stock.' uds') }}
                    </span>
                @endif

                <p class="font-semibold text-gray-800 text-sm leading-tight {{ $sinStock ? '' : 'group-hover:text-stock-primary' }} transition pr-12">
                    {{ $product->nombre }}
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ $product->marca }} · {{ $product->tamaño }}</p>
                <p class="{{ $sinStock ? 'text-gray-400' : 'text-stock-primary' }} font-bold mt-2 text-sm">{{ formatCurrency($product->precio_venta) }}</p>
                <p class="text-xs text-gray-400 font-mono mt-1">{{ $product->sku }}</p>
            </button>
        @empty
            @if($this->search)
                <div class="col-span-3">
                    <x-empty-state icon="box" title='Sin resultados para "{{ $this->search }}"'
                        description="Intenta con otro nombre, marca o SKU"/>
                </div>
            @else
                <div class="col-span-3">
                    <x-empty-state icon="box" title="Sin productos disponibles"
                        description="Agrega productos activos al inventario"/>
                </div>
            @endif
        @endforelse
    </div>

    @if($this->search && $this->products->count() > 0)
        <p class="text-xs text-gray-400 mt-2 text-center">
            {{ $this->products->count() }} resultado(s) para "{{ $this->search }}"
        </p>
    @endif
</div>
