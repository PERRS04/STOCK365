@extends('layouts.app')
@section('title', 'Entrada de Mercancía — ' . $almacen->nombre)
@section('content')

<div class="max-w-3xl space-y-5"
     x-data="{
         submitting: false,
         items: [{ product_id: '', cantidad: 1, costo_unitario: '' }],
         addItem()    { this.items.push({ product_id: '', cantidad: 1, costo_unitario: '' }); },
         removeItem(i){ if (this.items.length > 1) this.items.splice(i, 1); }
     }">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('almacenes.show', $almacen) }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← {{ $almacen->nombre }}</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Entrada de Mercancía</h1>
    </div>

    @if(session('error'))
    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-[13px] text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-[13px] text-red-700 space-y-1">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('almacenes.entry.store', $almacen) }}"
          @submit="submitting = true" class="space-y-5">
        @csrf

        {{-- Receipt header --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5 space-y-4">
            <h2 class="text-[13px] font-semibold text-gray-800">Datos de la entrada</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Proveedor</label>
                    <select name="provider_id"
                            class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                        <option value="">— Sin proveedor —</option>
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}" {{ old('provider_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Referencia / Factura</label>
                    <input type="text" name="referencia" value="{{ old('referencia') }}"
                           placeholder="Ej: FAC-2026-001"
                           class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Observaciones</label>
                <textarea name="observaciones" rows="2"
                          placeholder="Notas adicionales…"
                          class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary resize-none">{{ old('observaciones') }}</textarea>
            </div>
        </div>

        {{-- Products --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-[13px] font-semibold text-gray-800">Productos</h2>
                <button type="button" @click="addItem()"
                        class="text-[12px] text-stock-primary font-semibold hover:text-stock-primary/80 transition">
                    + Agregar producto
                </button>
            </div>

            <div class="divide-y divide-gray-50">
                <template x-for="(item, index) in items" :key="index">
                    <div class="px-5 py-4 flex flex-wrap gap-3 items-end">

                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Producto *</label>
                            <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" required
                                    class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                                <option value="">— Seleccionar —</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }} {{ $p->tamaño ? '('.$p->tamaño.')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-28">
                            <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Cantidad *</label>
                            <input type="number" :name="'items[' + index + '][cantidad]'" x-model.number="item.cantidad"
                                   min="1" required
                                   class="w-full px-3 py-2 text-[13px] text-center font-semibold border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                        </div>

                        <div class="w-36">
                            <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Costo unit. *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px] text-gray-400">$</span>
                                <input type="number" :name="'items[' + index + '][costo_unitario]'" x-model="item.costo_unitario"
                                       min="0" step="0.01" required
                                       class="w-full pl-7 pr-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                            </div>
                        </div>

                        <div class="w-28 text-right pb-0.5">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Subtotal</p>
                            <p class="text-[13px] font-semibold text-gray-700"
                               x-text="item.cantidad && item.costo_unitario ? '$' + (item.cantidad * item.costo_unitario).toFixed(2) : '—'">
                            </p>
                        </div>

                        <button type="button" @click="removeItem(index)"
                                x-show="items.length > 1"
                                class="p-2 text-gray-400 hover:text-red-500 transition mb-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Total row --}}
            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 flex justify-end gap-6">
                <span class="text-[12px] font-semibold text-gray-600">Total entrada:</span>
                <span class="text-[14px] font-bold text-gray-900"
                      x-text="'$' + items.reduce((s, i) => s + (parseFloat(i.cantidad || 0) * parseFloat(i.costo_unitario || 0)), 0).toFixed(2)">
                </span>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('almacenes.show', $almacen) }}"
               class="px-5 py-2.5 text-[13px] font-medium text-gray-600 border border-gray-200 rounded-xl hover:border-gray-300 hover:bg-gray-50 transition">
                Cancelar
            </a>
            <button type="submit"
                    :disabled="submitting"
                    :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                    class="px-7 py-2.5 bg-stock-primary text-white text-[13px] font-semibold rounded-xl hover:bg-stock-primary/90 transition shadow-sm">
                <span x-text="submitting ? 'Registrando…' : 'Registrar entrada'"></span>
            </button>
        </div>

    </form>

</div>
@endsection
