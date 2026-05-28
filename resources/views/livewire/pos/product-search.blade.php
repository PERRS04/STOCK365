<div class="h-full flex flex-col overflow-hidden">

    {{-- ── Search bar ─────────────────────────────────────────── --}}
    <div class="px-4 pt-3 pb-2.5 bg-[#f0f2f8] shrink-0">
        <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input
                id="pos-search"
                type="text"
                wire:model.live.debounce.200ms="search"
                @keydown.enter.prevent="$wire.selectFirst()"
                placeholder="Escanear código de barras o buscar — Enter añade el primero"
                autocomplete="off"
                spellcheck="false"
                class="w-full pl-10 pr-8 py-2.5 bg-white border border-gray-200/80 rounded-xl
                       focus:outline-none focus:ring-2 focus:ring-[#003594]/30 focus:border-[#003594]
                       text-[13px] text-gray-800 placeholder-gray-400
                       shadow-[0_1px_3px_rgba(0,0,0,0.05)] transition-all duration-150"
            />
            <div wire:loading.delay class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                <svg class="w-3.5 h-3.5 text-[#003594] animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
            </div>
            @if($search)
            <button wire:click="$set('search', '')"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center
                           text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-all">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            @endif
        </div>

        {{-- Marca filter pills --}}
        @if($this->marcas->isNotEmpty())
        <div class="flex items-center gap-1.5 mt-2 pills-scroll overflow-x-auto pb-0.5">
            <button
                wire:click="$set('marca', '')"
                class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-semibold border transition-all duration-120
                       {{ $marca === ''
                            ? 'bg-[#003594] text-white border-[#003594] shadow-sm'
                            : 'bg-white text-gray-500 border-gray-200 hover:border-[#003594] hover:text-[#003594]' }}"
            >Todos</button>
            @foreach($this->marcas as $m)
            <button
                wire:click="$set('marca', '{{ addslashes($m) }}')"
                class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-semibold border transition-all duration-120
                       {{ $marca === $m
                            ? 'bg-[#003594] text-white border-[#003594] shadow-sm'
                            : 'bg-white text-gray-500 border-gray-200 hover:border-[#003594] hover:text-[#003594]' }}"
            >{{ $m }}</button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Product grid ────────────────────────────────────────── --}}
    <div
        class="flex-1 overflow-y-auto pos-scroll px-4 pb-4 transition-opacity duration-150"
        wire:loading.class.delay="opacity-40"
    >
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5 mt-0.5">
            @forelse($this->products as $product)
            @php
                $stock     = $product->stock_sede;
                $sinStock  = $stock !== null && $stock === 0;
                $stockBajo = $stock !== null && $stock > 0 && $stock < $product->stock_minimo;
            @endphp

            <button
                type="button"
                onclick="
                    this.classList.add('card-press');
                    this.addEventListener('animationend', () => this.classList.remove('card-press'), { once: true });
                    window.dispatchEvent(new CustomEvent('add-to-cart', { detail: {
                        id:     {{ $product->id }},
                        nombre: {{ json_encode($product->nombre) }},
                        precio: {{ (float)$product->precio_venta }},
                        stock:  {{ $stock ?? 99 }}
                    }}));
                "
                @if($sinStock) disabled @endif
                class="relative bg-white rounded-xl p-3 text-left transition-all duration-120 group
                       border-2 focus:outline-none
                       {{ $sinStock
                            ? 'border-gray-100 opacity-40 cursor-not-allowed'
                            : 'border-transparent hover:border-[#003594] hover:shadow-[0_2px_12px_rgba(0,53,148,0.10)] focus:border-[#003594] active:scale-[0.97]' }}"
            >
                {{-- Stock badge --}}
                @if($stock !== null)
                <span class="absolute top-2 right-2 text-[9px] font-bold px-1.5 py-0.5 rounded-full leading-none tabular-nums
                    {{ $sinStock  ? 'bg-red-50 text-red-400'
                      : ($stockBajo ? 'bg-amber-50 text-amber-600'
                      :               'bg-emerald-50 text-emerald-600') }}">
                    {{ $sinStock ? '0' : $stock }}
                </span>
                @endif

                {{-- Name --}}
                <p class="font-semibold text-gray-800 text-[13px] leading-snug pr-7 line-clamp-2
                          {{ $sinStock ? '' : 'group-hover:text-[#003594]' }} transition-colors duration-120">
                    {{ $product->nombre }}
                </p>

                {{-- Brand / size --}}
                @if($product->marca || $product->tamaño)
                <p class="text-[10px] text-gray-400 mt-1 truncate leading-none">
                    {{ implode(' · ', array_filter([$product->marca, $product->tamaño])) }}
                </p>
                @endif

                {{-- Price --}}
                <p class="text-[15px] font-bold mt-2 leading-none tabular-nums
                          {{ $sinStock ? 'text-gray-300' : 'text-[#003594]' }}">
                    ${{ number_format($product->precio_venta, 2) }}
                </p>

                {{-- Low stock indicator --}}
                @if($stockBajo)
                <div class="mt-1.5 flex items-center gap-1">
                    <span class="w-1 h-1 rounded-full bg-amber-400 animate-pulse"></span>
                    <span class="text-[9px] text-amber-500 font-bold tracking-wide">BAJO</span>
                </div>
                @endif

                {{-- Hover add icon --}}
                @unless($sinStock)
                <div class="absolute bottom-2 right-2 w-5 h-5 rounded-full bg-[#003594] flex items-center justify-center
                            opacity-0 group-hover:opacity-100 transition-opacity duration-120 shadow-sm">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                @endunless
            </button>

            @empty

            {{-- Empty state --}}
            <div class="col-span-full flex flex-col items-center justify-center py-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center mb-4">
                    @if($search)
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    @else
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    @endif
                </div>
                @if($search)
                    <p class="text-[14px] font-semibold text-gray-500">Sin resultados para "{{ $search }}"</p>
                    <p class="text-[12px] text-gray-400 mt-1">Intenta con otro nombre, marca o código</p>
                @elseif($marca)
                    <p class="text-[14px] font-semibold text-gray-500">Sin productos en {{ $marca }}</p>
                @else
                    <p class="text-[14px] font-semibold text-gray-500">Sin productos disponibles</p>
                    <p class="text-[12px] text-gray-400 mt-1">Activa productos en el inventario</p>
                @endif
            </div>

            @endforelse
        </div>

        {{-- Result count --}}
        @if($this->products->isNotEmpty() && ($search || $marca))
        <p class="text-[11px] text-gray-400 text-center mt-3 pb-1">
            {{ $this->products->count() }} producto(s)
            @if($search) para "{{ $search }}" @endif
            @if($marca) en {{ $marca }} @endif
        </p>
        @endif
    </div>
</div>
