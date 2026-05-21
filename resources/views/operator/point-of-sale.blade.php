@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')
<div
    x-data="cart('{{ route('sales.store') }}')"
    @add-to-cart.window="addItem($event.detail)"
    @keydown.escape.window="handleEsc()"
    @keydown.f2.window.prevent="submitSale()"
    @keydown.ctrl.k.window.prevent="focusSearch()"
    @keydown.ctrl.b.window.prevent="focusSearch()"
    class="space-y-4"
>
    {{-- Session Banner --}}
    @if($cashSession)
    <div class="bg-emerald-50 border border-emerald-200/80 rounded-xl px-4 py-2.5 flex items-center gap-3">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <span class="text-[12px] text-emerald-700 font-semibold">Caja abierta</span>
        <span class="text-emerald-400 text-[11px]">·</span>
        <span class="text-[12px] text-emerald-600">Desde {{ $cashSession->opened_at->format('H:i') }}</span>
        <span class="text-emerald-400 text-[11px]">·</span>
        <span class="text-[12px] text-emerald-600">{{ $cashSession->sales_count }} venta(s) en el turno</span>
        <a href="{{ route('cash-session.status') }}" class="ml-auto text-[11px] text-emerald-700 hover:underline font-medium">Ver estado →</a>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-[18px] font-semibold text-gray-900">Punto de venta</h1>
            <p class="text-[12px] text-gray-400 mt-0.5">{{ $sede->nombre }}</p>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs text-gray-400 hidden md:flex gap-3">
                <span class="bg-gray-100 px-2 py-1 rounded font-mono">Ctrl+B</span> buscar ·
                <span class="bg-gray-100 px-2 py-1 rounded font-mono">F2</span> cobrar ·
                <span class="bg-gray-100 px-2 py-1 rounded font-mono">Esc</span> cancelar
            </span>
            <span class="text-sm text-gray-500">{{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Products (Livewire) --}}
        <div class="lg:col-span-2">
            <livewire:pos.product-search />
        </div>

        {{-- Cart (Alpine.js — client-side for speed) --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] flex flex-col min-h-96">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Carrito</h3>
                <span class="text-xs text-gray-400" x-text="items.length + ' item(s)'"></span>
            </div>

            <div class="flex-1 p-4 overflow-y-auto">
                <template x-if="items.length === 0">
                    <div class="text-center text-gray-400 py-8">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-sm">Selecciona productos</p>
                        <p class="text-xs mt-1 opacity-60">o presiona Ctrl+B para buscar</p>
                    </div>
                </template>

                <div class="space-y-2">
                    <template x-for="item in items" :key="item.id">
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded hover:bg-gray-100 transition group">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" x-text="item.nombre"></p>
                                <p class="text-xs text-gray-500" x-text="formatCurrency(item.precio) + ' c/u'"></p>
                            </div>
                            <div class="flex items-center gap-1 ml-2">
                                <button
                                    @click="decreaseItem(item)"
                                    class="w-6 h-6 rounded-full bg-gray-200 hover:bg-red-100 text-gray-600 text-sm font-bold focus:outline-none transition">−</button>
                                <span class="w-6 text-center text-sm font-bold" x-text="item.cantidad"></span>
                                <button
                                    @click="item.cantidad++"
                                    class="w-6 h-6 rounded-full bg-gray-200 hover:bg-green-100 text-gray-600 text-sm font-bold focus:outline-none transition">+</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <label class="text-gray-600">Descuento (USD)</label>
                    <input
                        type="number"
                        x-model="descuento"
                        min="0" step="0.50"
                        class="w-24 border border-gray-300 rounded px-2 py-1 text-right text-sm focus:outline-none focus:ring-2 focus:ring-stock-primary"
                    />
                </div>

                <div class="flex justify-between font-bold text-lg items-center">
                    <span>TOTAL</span>
                    <span class="text-stock-primary" x-text="formatCurrency(netTotal)"></span>
                </div>

                <button
                    @click="submitSale()"
                    :disabled="items.length === 0 || loading"
                    class="w-full py-3 bg-stock-accent text-stock-primary rounded-lg font-bold text-lg hover:bg-yellow-400 transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-stock-primary flex items-center justify-center gap-2"
                >
                    <span x-show="!loading">COBRAR <span class="text-sm font-normal opacity-70">F2</span></span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Procesando…
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Success Modal --}}
    <div x-show="success" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">¡Venta Registrada!</h3>
            <p class="text-gray-600 mb-1">Total cobrado:</p>
            <p class="text-3xl font-bold text-stock-primary mb-6" x-text="formatCurrency(lastTotal)"></p>
            <button
                @click="resetCart()"
                class="w-full py-2 bg-stock-primary text-white rounded-lg font-medium hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-stock-primary">
                Nueva Venta
            </button>
        </div>
    </div>
</div>

<script>
function cart(storeUrl) {
    return {
        items:    [],
        descuento: 0,
        success:  false,
        loading:  false,
        lastTotal: 0,
        storeUrl,

        get subtotal() {
            return this.items.reduce((s, i) => s + i.precio * i.cantidad, 0);
        },
        get netTotal() {
            return Math.max(0, this.subtotal - (parseFloat(this.descuento) || 0));
        },

        addItem(detail) {
            const existing = this.items.find(i => i.id === detail.id);
            if (existing) {
                existing.cantidad++;
            } else {
                this.items.push({ id: detail.id, nombre: detail.nombre, precio: detail.precio, cantidad: 1 });
            }
        },

        decreaseItem(item) {
            item.cantidad--;
            if (item.cantidad <= 0) {
                this.items = this.items.filter(i => i.id !== item.id);
            }
        },

        focusSearch() {
            document.getElementById('pos-search')?.focus();
        },

        handleEsc() {
            if (this.success) { this.resetCart(); return; }
            if (this.items.length === 0) return;
            Swal.fire({
                title: '¿Cancelar venta?',
                text: 'Se perderán los productos del carrito',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Cancelar Venta',
                cancelButtonText: 'Volver',
                reverseButtons: true,
            }).then(r => { if (r.isConfirmed) this.clearCart(); });
        },

        clearCart() {
            this.items    = [];
            this.descuento = 0;
        },

        resetCart() {
            this.success  = false;
            this.lastTotal = 0;
            this.clearCart();
        },

        async submitSale() {
            if (this.items.length === 0 || this.loading) return;
            this.loading = true;
            const payload = {
                items: this.items.map(i => ({
                    product_id:       i.id,
                    cantidad:         i.cantidad,
                    precio_unitario:  i.precio,
                })),
                descuento: parseFloat(this.descuento) || 0,
                _token: document.querySelector('meta[name="csrf-token"]').content,
            };
            try {
                const res = await fetch(this.storeUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': payload._token,
                    },
                    body: JSON.stringify(payload),
                });
                if (res.ok) {
                    this.lastTotal = this.netTotal;
                    this.success   = true;
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
@endsection
