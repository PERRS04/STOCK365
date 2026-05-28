@extends('layouts.pos')

@section('pos-content')
<div
    x-data="posCart('{{ route('sales.store') }}')"
    x-init="init()"
    @add-to-cart.window="addItem($event.detail)"
    @keydown.escape.window="handleEsc()"
    @keydown.f2.window.prevent="submitSale()"
    @keydown.ctrl.b.window.prevent="focusSearch()"
    @keydown.ctrl.k.window.prevent="focusSearch()"
    class="flex h-full w-full overflow-hidden"
>

{{-- ════════════════════════════════════════════════════════════
     LEFT — Product Zone
     ════════════════════════════════════════════════════════════ --}}
<div class="flex-1 flex flex-col overflow-hidden min-w-0">
    <livewire:pos.product-search />
</div>

{{-- ════════════════════════════════════════════════════════════
     RIGHT — Cart Panel
     ════════════════════════════════════════════════════════════ --}}
<div class="w-[340px] xl:w-[370px] flex flex-col bg-white border-l border-gray-200/70 overflow-hidden shrink-0">

    {{-- ── Cart header ──────────────────────────────────────── --}}
    <div class="px-4 py-3 border-b border-gray-100 shrink-0">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[13px] font-bold text-gray-800">Carrito</span>
                <span
                    x-show="items.length > 0"
                    x-transition
                    class="w-5 h-5 rounded-full bg-[#003594] flex items-center justify-center">
                    <span class="text-[10px] font-bold text-white tabular-nums leading-none" x-text="items.length"></span>
                </span>
            </div>
            {{-- Session context --}}
            @if($cashSession)
            <div class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                <span class="text-[11px] text-gray-400 font-medium">
                    Caja · {{ $cashSession->opened_at->format('H:i') }}
                    @if($cashSession->sales_count > 0)
                    · {{ $cashSession->sales_count }}v
                    @endif
                </span>
            </div>
            @else
            <span class="text-[11px] text-amber-500 font-medium">Sin caja abierta</span>
            @endif
        </div>
    </div>

    {{-- ── Cart items (scrollable) ───────────────────────────── --}}
    <div class="flex-1 overflow-y-auto pos-scroll min-h-0">

        {{-- Empty state --}}
        <template x-if="items.length === 0">
            <div class="flex flex-col items-center justify-center h-full py-8 px-6 text-center select-none">
                <div class="w-14 h-14 rounded-2xl bg-[#f0f2f8] border border-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-[13px] font-semibold text-gray-400">Carrito vacío</p>
                <p class="text-[11px] text-gray-300 mt-1">Selecciona productos o<br>presiona <kbd class="bg-gray-100 rounded px-1 font-mono text-[10px] text-gray-400">Ctrl+B</kbd> para buscar</p>
            </div>
        </template>

        {{-- Item list --}}
        <div class="p-3 space-y-1.5">
            <template x-for="item in items" :key="item.id">
                <div
                    x-show="true"
                    x-transition:enter="transition ease-out duration-180"
                    x-transition:enter-start="opacity-0 -translate-y-1.5"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-120"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="group flex items-start gap-2 p-2.5 rounded-xl bg-[#f8f9fc] hover:bg-[#f0f2f8] transition-colors duration-120"
                >
                    {{-- Product info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-[12px] font-semibold text-gray-800 leading-tight truncate" x-text="item.nombre"></p>
                        <p class="text-[11px] text-gray-400 mt-0.5 tabular-nums">
                            <span x-text="'$' + item.precio.toFixed(2)"></span>
                            <span class="text-gray-300 mx-0.5">×</span>
                            <span x-text="item.cantidad"></span>
                            <span class="text-gray-300 mx-0.5">=</span>
                            <span class="font-semibold text-gray-600" x-text="'$' + (item.precio * item.cantidad).toFixed(2)"></span>
                        </p>
                    </div>

                    {{-- Quantity controls --}}
                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            @click="decreaseItem(item)"
                            class="w-6 h-6 rounded-lg bg-white border border-gray-200 flex items-center justify-center
                                   text-gray-500 hover:text-red-500 hover:border-red-200 hover:bg-red-50
                                   transition-all duration-120 text-[14px] font-bold focus:outline-none"
                        >−</button>
                        <span
                            x-text="item.cantidad"
                            :class="item._bump ? 'qty-bump' : ''"
                            @animationend="item._bump = false"
                            class="w-6 text-center text-[12px] font-bold text-gray-700 tabular-nums select-none"
                        ></span>
                        <button
                            @click="increaseItem(item)"
                            class="w-6 h-6 rounded-lg bg-white border border-gray-200 flex items-center justify-center
                                   text-gray-500 hover:text-[#003594] hover:border-[#003594]/30 hover:bg-blue-50
                                   transition-all duration-120 text-[14px] font-bold focus:outline-none"
                        >+</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── Cart footer ───────────────────────────────────────── --}}
    <div class="shrink-0 border-t border-gray-100">

        {{-- Subtotal row --}}
        <template x-if="items.length > 0">
            <div class="px-4 pt-3 pb-1 flex items-center justify-between">
                <span class="text-[11px] text-gray-400 font-medium">Subtotal</span>
                <span class="text-[12px] font-semibold text-gray-600 tabular-nums" x-text="'$' + subtotal.toFixed(2)"></span>
            </div>
        </template>

        {{-- Discount --}}
        <div class="px-4 py-2 flex items-center justify-between gap-3">
            <label class="text-[11px] text-gray-400 font-medium shrink-0">Descuento</label>
            <div class="flex items-center gap-1.5">
                <span class="text-[11px] text-gray-400">$</span>
                <input
                    type="number"
                    x-model="descuento"
                    min="0"
                    step="0.50"
                    placeholder="0.00"
                    class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-right text-[12px] font-semibold
                           text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]
                           transition bg-white tabular-nums"
                />
            </div>
        </div>

        {{-- Total — DOMINANT ──────────────────────────────── --}}
        <div class="px-4 pb-3">
            <div class="bg-[#f8f9fc] rounded-2xl px-4 py-3 border border-gray-100">
                <div class="flex items-baseline justify-between">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total</span>
                    <span
                        id="pos-total"
                        x-text="'$' + netTotal.toFixed(2)"
                        @animationend="$el.classList.remove('total-pop')"
                        class="text-[32px] xl:text-[36px] font-black text-[#003594] tabular-nums leading-none tracking-tight"
                    ></span>
                </div>
                <template x-if="descuento > 0">
                    <p class="text-[10px] text-emerald-500 font-semibold mt-1 text-right">
                        − $<span x-text="parseFloat(descuento).toFixed(2)"></span> descuento aplicado
                    </p>
                </template>
            </div>
        </div>

        {{-- COBRAR button --}}
        <div class="px-4 pb-4">
            <button
                @click="submitSale()"
                :disabled="items.length === 0 || loading"
                class="w-full h-12 rounded-xl font-bold text-[15px] flex items-center justify-center gap-3
                       bg-[#003594] text-white hover:bg-[#002470] active:scale-[0.98]
                       disabled:opacity-35 disabled:cursor-not-allowed disabled:active:scale-100
                       transition-all duration-120 focus:outline-none focus:ring-2 focus:ring-[#003594]/40
                       shadow-[0_4px_16px_rgba(0,53,148,0.25)]"
            >
                <template x-if="!loading">
                    <span class="flex items-center gap-3">
                        <span>COBRAR</span>
                        <kbd class="text-[10px] font-normal text-white/50 bg-white/10 rounded px-1.5 py-0.5 leading-none">F2</kbd>
                    </span>
                </template>
                <template x-if="loading">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Procesando…
                    </span>
                </template>
            </button>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     SUCCESS MODAL
     ════════════════════════════════════════════════════════════ --}}
<div
    x-show="success"
    x-cloak
    @keydown.enter.window="if(success) resetCart()"
    class="fixed inset-0 z-50 flex items-center justify-center"
    style="background: rgba(0,18,51,0.6); backdrop-filter: blur(6px);"
>
    <div
        x-show="success"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-2xl shadow-[0_24px_80px_rgba(0,0,0,0.25)] w-full max-w-xs mx-4 overflow-hidden"
    >
        {{-- Top accent --}}
        <div class="h-1.5 bg-[#003594]"></div>

        <div class="p-8 text-center">
            {{-- Animated check --}}
            <div class="w-16 h-16 rounded-full bg-emerald-50 border-2 border-emerald-200 flex items-center justify-center mx-auto mb-5">
                <svg class="w-9 h-9 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                    <path class="check-draw" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Venta registrada</p>
            <p class="text-[38px] font-black text-[#003594] tabular-nums leading-none" x-text="'$' + lastTotal.toFixed(2)"></p>

            <template x-if="lastItemCount > 0">
                <p class="text-[12px] text-gray-400 mt-2" x-text="lastItemCount + ' producto(s) vendido(s)'"></p>
            </template>

            <button
                @click="resetCart()"
                autofocus
                class="mt-6 w-full h-11 bg-[#003594] text-white rounded-xl font-bold text-[13px]
                       hover:bg-[#002470] active:scale-[0.98] transition-all duration-120
                       focus:outline-none focus:ring-2 focus:ring-[#003594]/40
                       flex items-center justify-center gap-2"
            >
                Nueva Venta
                <kbd class="text-[10px] font-normal text-white/50 bg-white/10 rounded px-1.5 py-0.5 leading-none">Enter</kbd>
            </button>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
function posCart(storeUrl) {
    return {
        items:         [],
        descuento:     0,
        success:       false,
        loading:       false,
        lastTotal:     0,
        lastItemCount: 0,
        storeUrl,

        get subtotal() {
            return this.items.reduce((s, i) => s + i.precio * i.cantidad, 0);
        },
        get netTotal() {
            return Math.max(0, this.subtotal - (parseFloat(this.descuento) || 0));
        },

        init() {
            // Animate total when it changes
            this.$watch('netTotal', () => {
                const el = document.getElementById('pos-total');
                if (!el) return;
                el.classList.remove('total-pop');
                requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('total-pop')));
            });
            // Auto-focus search on load
            this.$nextTick(() => {
                setTimeout(() => document.getElementById('pos-search')?.focus(), 150);
            });
        },

        addItem(detail) {
            const existing = this.items.find(i => i.id === detail.id);
            if (existing) {
                existing.cantidad++;
                existing._bump = true;
            } else {
                this.items.push({
                    id:       detail.id,
                    nombre:   detail.nombre,
                    precio:   detail.precio,
                    cantidad: 1,
                    _bump:    false,
                });
            }
        },

        decreaseItem(item) {
            if (item.cantidad <= 1) {
                this.items = this.items.filter(i => i.id !== item.id);
            } else {
                item.cantidad--;
                item._bump = true;
            }
        },

        increaseItem(item) {
            item.cantidad++;
            item._bump = true;
        },

        focusSearch() {
            document.getElementById('pos-search')?.focus();
            document.getElementById('pos-search')?.select();
        },

        handleEsc() {
            if (this.success)  { this.resetCart(); return; }
            if (this.loading)  return;
            if (this.items.length === 0) { this.focusSearch(); return; }
            Swal.fire({
                title: '¿Cancelar venta?',
                text:  'Se perderán los productos del carrito.',
                icon:  'warning',
                showCancelButton:    true,
                confirmButtonColor:  '#dc2626',
                cancelButtonColor:   '#6b7280',
                confirmButtonText:   'Cancelar venta',
                cancelButtonText:    'Continuar',
                reverseButtons:      true,
                customClass:         { popup: 'rounded-2xl' },
            }).then(r => {
                if (r.isConfirmed) {
                    this.clearCart();
                    this.$nextTick(() => this.focusSearch());
                }
            });
        },

        clearCart() {
            this.items    = [];
            this.descuento = 0;
        },

        resetCart() {
            this.success       = false;
            this.lastTotal     = 0;
            this.lastItemCount = 0;
            this.clearCart();
            this.$nextTick(() => this.focusSearch());
        },

        async submitSale() {
            if (this.items.length === 0 || this.loading) return;
            this.loading = true;

            const payload = {
                items: this.items.map(i => ({
                    product_id:      i.id,
                    cantidad:        i.cantidad,
                    precio_unitario: i.precio,
                })),
                descuento: parseFloat(this.descuento) || 0,
            };

            try {
                const res = await fetch(this.storeUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });

                if (res.ok) {
                    this.lastTotal     = this.netTotal;
                    this.lastItemCount = this.items.reduce((s, i) => s + i.cantidad, 0);
                    this.success       = true;
                } else if (res.status === 422) {
                    const body = await res.json();
                    window.toast(body.error || 'Error de validación. Verifique el carrito.', 'error');
                } else {
                    window.toast('Error al registrar la venta. Intente de nuevo.', 'error');
                }
            } catch {
                window.toast('Error de conexión. Verifique su red.', 'error');
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
