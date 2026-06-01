<div class="h-full flex flex-col overflow-hidden bg-[#f4f6fb]">

    {{-- ── Search + Header bar ──────────────────────────────────── --}}
    <div class="px-5 pt-4 pb-3 bg-[#f4f6fb] shrink-0">
        <div class="relative">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input
                id="pos-search"
                type="text"
                wire:model.live.debounce.200ms="search"
                @keydown.enter.prevent="$wire.selectFirst()"
                placeholder="Buscar producto o escanear código…"
                autocomplete="off"
                spellcheck="false"
                class="w-full pl-12 pr-10 py-3.5 bg-white border border-gray-200/70 rounded-2xl
                       focus:outline-none focus:ring-2 focus:ring-[#003594]/25 focus:border-[#003594]
                       text-[14px] text-gray-900 placeholder-gray-400
                       shadow-[0_1px_4px_rgba(0,0,0,0.06)] transition-all duration-150"
            />
            <div wire:loading.delay class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                <svg class="w-4 h-4 text-[#003594] animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
            </div>
            @if($search)
            <button wire:click="$set('search', '')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center
                           text-gray-400 hover:text-gray-700 rounded-full hover:bg-gray-100 transition-all">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            @endif
        </div>

        {{-- Category filter pills --}}
        @if($this->marcas->isNotEmpty())
        <div class="flex items-center gap-2 mt-3 pills-scroll overflow-x-auto pb-0.5">
            <button
                wire:click="$set('marca', '')"
                class="shrink-0 px-4 py-1.5 rounded-full text-[12px] font-semibold border transition-all duration-120
                       {{ $marca === ''
                            ? 'bg-[#003594] text-white border-[#003594] shadow-sm'
                            : 'bg-white text-gray-500 border-gray-200 hover:border-[#003594]/40 hover:text-[#003594]' }}"
            >Todos</button>
            @foreach($this->marcas as $m)
            <button
                wire:click="$set('marca', '{{ addslashes($m) }}')"
                class="shrink-0 px-4 py-1.5 rounded-full text-[12px] font-semibold border transition-all duration-120
                       {{ $marca === $m
                            ? 'bg-[#003594] text-white border-[#003594] shadow-sm'
                            : 'bg-white text-gray-500 border-gray-200 hover:border-[#003594]/40 hover:text-[#003594]' }}"
            >{{ $m }}</button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Product grid ─────────────────────────────────────────── --}}
    <div
        class="flex-1 overflow-y-auto pos-scroll px-5 pb-5 transition-opacity duration-150"
        wire:loading.class.delay="opacity-40"
    >
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 mt-0.5">
            @forelse($this->products as $product)
            @php
                $stock     = $product->stock_sede;
                $sinStock  = $stock !== null && $stock === 0;
                $stockBajo = $stock !== null && $stock > 0 && $stock < $product->stock_minimo;
            @endphp

            <button
                type="button"
                @unless($sinStock)
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
                @endunless
                @if($sinStock) disabled @endif
                class="relative bg-white rounded-2xl p-4 text-left flex flex-col min-h-[130px]
                       focus:outline-none transition-all duration-150 group overflow-hidden
                       {{ $sinStock
                            ? 'cursor-not-allowed opacity-45 border-2 border-gray-100'
                            : ($stockBajo
                                ? 'border-2 border-amber-200/80 hover:border-amber-400 hover:shadow-[0_4px_20px_rgba(245,158,11,0.12)] active:scale-[0.97] focus:border-amber-400'
                                : 'border-2 border-transparent hover:border-[#003594] hover:shadow-[0_4px_20px_rgba(0,53,148,0.10)] active:scale-[0.97] focus:border-[#003594]')
                       }} shadow-[0_1px_4px_rgba(0,0,0,0.05)]"
            >

                {{-- Low stock left accent bar --}}
                @if($stockBajo && !$sinStock)
                <div class="absolute left-0 top-3 bottom-3 w-[3px] rounded-r-full bg-amber-400"></div>
                @endif

                {{-- Blocked overlay --}}
                @if($sinStock)
                <div class="absolute inset-0 flex items-center justify-center z-10 rounded-2xl bg-white/60">
                    <span class="text-[10px] font-black uppercase tracking-[0.18em] text-red-400
                                 bg-red-50 border border-red-100 px-3 py-1.5 rounded-full">
                        AGOTADO
                    </span>
                </div>
                @endif

                {{-- Stock indicator (top right) --}}
                @if($stock !== null)
                <div class="absolute top-3 right-3">
                    @if($sinStock)
                    <span class="flex items-center gap-1 text-[10px] font-bold text-red-300 bg-red-50 px-2 py-0.5 rounded-full">
                        <span class="w-1 h-1 rounded-full bg-red-300"></span>0
                    </span>
                    @elseif($stockBajo)
                    <span class="flex items-center gap-1 text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200/60">
                        <span class="w-1 h-1 rounded-full bg-amber-400 animate-pulse"></span>{{ $stock }}
                    </span>
                    @else
                    <span class="flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/40">
                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span>{{ $stock }}
                    </span>
                    @endif
                </div>
                @endif

                {{-- Product name --}}
                <p class="text-[14px] font-bold leading-snug pr-10 line-clamp-2 transition-colors duration-120
                          {{ $sinStock ? 'text-gray-300' : ($stockBajo ? 'text-gray-800 group-hover:text-amber-700' : 'text-gray-900 group-hover:text-[#003594]') }}">
                    {{ $product->nombre }}
                </p>

                {{-- Brand / size --}}
                @if($product->marca || $product->tamaño)
                <p class="text-[11px] text-gray-400 mt-0.5 truncate">
                    {{ implode(' · ', array_filter([$product->marca, $product->tamaño])) }}
                </p>
                @endif

                {{-- Stock description line --}}
                @if($stock !== null && !$sinStock)
                <p class="text-[11px] font-medium mt-2 {{ $stockBajo ? 'text-amber-500' : 'text-emerald-500' }}">
                    {{ $stock }} disponible{{ $stock !== 1 ? 's' : '' }}
                    @if($stockBajo) &middot; stock bajo @endif
                </p>
                @endif

                <div class="flex-1 min-h-[8px]"></div>

                {{-- Price --}}
                <p class="text-[20px] font-black leading-none tabular-nums mt-2 transition-colors duration-120
                          {{ $sinStock ? 'text-gray-200' : ($stockBajo ? 'text-amber-600' : 'text-[#003594]') }}">
                    ${{ number_format($product->precio_venta, 2) }}
                </p>

                {{-- Hover add icon --}}
                @unless($sinStock)
                <div class="absolute bottom-3 right-3 w-7 h-7 rounded-full bg-[#003594] flex items-center justify-center
                            opacity-0 group-hover:opacity-100 transition-all duration-150 shadow-md
                            {{ $stockBajo ? 'bg-amber-500' : 'bg-[#003594]' }}">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                @endunless

            </button>

            @empty

            {{-- Empty state --}}
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center mb-5">
                    @if($search)
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    @else
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    @endif
                </div>
                @if($search)
                    <p class="text-[15px] font-semibold text-gray-500">Sin resultados para "{{ $search }}"</p>
                    <p class="text-[13px] text-gray-400 mt-1.5">Intenta con otro nombre, marca o código</p>
                @elseif($marca)
                    <p class="text-[15px] font-semibold text-gray-500">Sin productos en {{ $marca }}</p>
                    <button wire:click="$set('marca', '')" class="mt-3 text-[12px] text-[#003594] font-medium hover:underline">
                        Ver todos los productos
                    </button>
                @else
                    <p class="text-[15px] font-semibold text-gray-500">Sin productos disponibles</p>
                    <p class="text-[13px] text-gray-400 mt-1.5">Activa productos en el inventario para comenzar</p>
                @endif
            </div>

            @endforelse
        </div>

        {{-- Result count --}}
        @if($this->products->isNotEmpty() && ($search || $marca))
        <p class="text-[11px] text-gray-400 text-center mt-4 pb-1">
            {{ $this->products->count() }} resultado{{ $this->products->count() !== 1 ? 's' : '' }}
            @if($search) para "{{ $search }}" @endif
            @if($marca) en {{ $marca }} @endif
        </p>
        @endif
    </div>
</div>
