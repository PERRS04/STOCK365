@extends('layouts.app')
@section('title', 'Recepción #' . $receipt->id)
@section('content')

<div class="space-y-5 max-w-4xl">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('inventory-receipts.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Recepciones</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Recepción #{{ $receipt->id }}</h1>
        @php
            $stMap = ['pendiente' => 'bg-amber-50 text-amber-700 border-amber-200', 'aprobado' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'rechazado' => 'bg-red-50 text-red-700 border-red-200'];
            $stLabel = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];
        @endphp
        <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-semibold {{ $stMap[$receipt->estado] ?? '' }}">
            {{ $stLabel[$receipt->estado] ?? $receipt->estado }}
        </span>
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- Left: Receipt details --}}
        <div class="col-span-1 space-y-4">

            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[13px] font-semibold text-gray-800">Datos de la recepción</h2>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div class="flex items-start justify-between">
                        <span class="text-[11px] text-gray-500">Proveedor</span>
                        <span class="text-[12px] font-semibold text-gray-800 text-right max-w-[60%]">{{ $receipt->supplier_name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-gray-500">Monto pagado</span>
                        <span class="text-[14px] font-bold text-gray-900">${{ number_format($receipt->monto_pagado, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-gray-500">Sede</span>
                        <span class="text-[12px] font-medium text-gray-800">{{ $receipt->sede?->nombre ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-gray-500">Registrado por</span>
                        <span class="text-[12px] font-medium text-gray-800">{{ $receipt->user?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-gray-500">Fecha</span>
                        <span class="text-[12px] text-gray-600">{{ $receipt->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($receipt->observaciones)
                    <div class="pt-2 border-t border-gray-100">
                        <p class="text-[11px] text-gray-500 mb-1">Observaciones</p>
                        <p class="text-[12px] text-gray-700">{{ $receipt->observaciones }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Invoice file --}}
            @if($receipt->invoice_path)
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[13px] font-semibold text-gray-800">Factura adjunta</h2>
                </div>
                <div class="p-5">
                    @php $ext = pathinfo($receipt->invoice_path, PATHINFO_EXTENSION); @endphp
                    @if(in_array(strtolower($ext), ['jpg','jpeg','png','webp']))
                        <img src="{{ Storage::url($receipt->invoice_path) }}" alt="Factura" class="w-full rounded-lg border border-gray-200">
                    @else
                        <a href="{{ Storage::url($receipt->invoice_path) }}" target="_blank"
                           class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-stock-primary hover:bg-blue-50/50 transition-colors group">
                            <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-[12px] font-medium text-gray-700 group-hover:text-stock-primary transition-colors">Ver PDF</span>
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Approval info (if processed) --}}
            @if(! $receipt->isPending())
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[13px] font-semibold text-gray-800">Resolución</h2>
                </div>
                <div class="px-5 py-4 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-gray-500">Por</span>
                        <span class="text-[12px] font-medium text-gray-800">{{ $receipt->approvedBy?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-gray-500">Fecha</span>
                        <span class="text-[12px] text-gray-600">{{ $receipt->aprobado_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($receipt->notas_aprobacion)
                    <div class="pt-2 border-t border-gray-100">
                        <p class="text-[11px] text-gray-500 mb-1">Notas</p>
                        <p class="text-[12px] text-gray-700">{{ $receipt->notas_aprobacion }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- Right: Approval / items --}}
        <div class="col-span-2">

            @if($receipt->isPending())
            {{-- Approval form --}}
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]"
                 x-data="{
                     items: [{ product_id: '', cantidad: 1, costo_unitario: '' }],
                     montoPagado: {{ $receipt->monto_pagado }},
                     addItem() { this.items.push({ product_id: '', cantidad: 1, costo_unitario: '' }) },
                     removeItem(i) { if(this.items.length > 1) this.items.splice(i, 1) },
                     total() { return this.items.reduce((s, it) => s + (parseFloat(it.cantidad)||0) * (parseFloat(it.costo_unitario)||0), 0) },
                     diffOk() { return Math.abs(this.total() - this.montoPagado) < 0.005 }
                 }">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[13px] font-semibold text-gray-800">Aprobar recepción — Ingresar productos</h2>
                    <p class="text-[11px] text-gray-400 mt-0.5">Registra cada producto recibido. Al aprobar, el stock se actualiza automáticamente.</p>
                </div>

                <form action="{{ route('inventory-receipts.approve', $receipt) }}" method="POST">
                    @csrf
                    <div class="p-5 space-y-4">

                        @error('sede_id_override')
                            <p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $message }}</p>
                        @enderror

                        @if($errors->has('monto_items'))
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-[12px] font-bold text-red-700 mb-3">Aprobación bloqueada — discrepancia de montos</p>
                            <div class="grid grid-cols-3 gap-2 text-center mb-3">
                                <div class="bg-white rounded-lg border border-red-100 px-3 py-2">
                                    <p class="text-[10px] text-gray-400 mb-0.5">Monto registrado</p>
                                    <p class="text-[14px] font-bold text-gray-900">${{ number_format(session('audit_monto_pagado', 0), 2) }}</p>
                                </div>
                                <div class="bg-white rounded-lg border border-red-100 px-3 py-2">
                                    <p class="text-[10px] text-gray-400 mb-0.5">Total calculado</p>
                                    <p class="text-[14px] font-bold text-gray-900">${{ number_format(session('audit_monto_calculado', 0), 2) }}</p>
                                </div>
                                <div class="bg-red-100 rounded-lg border border-red-200 px-3 py-2">
                                    <p class="text-[10px] text-red-500 mb-0.5">Diferencia</p>
                                    <p class="text-[14px] font-bold text-red-700">${{ number_format(abs(session('audit_monto_diferencia', 0)), 2) }}</p>
                                </div>
                            </div>
                            <p class="text-[11px] text-red-600">{{ $errors->first('monto_items') }}</p>
                        </div>
                        @endif

                        {{-- Sede selector for receipts with no sede (created by boss) --}}
                        @if($receipt->sede_id === null)
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg space-y-2">
                            <p class="text-[11px] font-semibold text-amber-700">Esta recepción no tiene sede asignada. Selecciona la sede de destino:</p>
                            <select name="sede_id_override" required
                                    class="w-full px-2.5 py-2 text-[12px] border border-amber-300 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-1 focus:ring-stock-primary/20">
                                <option value="">— Seleccionar sede —</option>
                                @foreach(\App\Models\Sede::where('activa', true)->orderBy('nombre')->get() as $s)
                                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Items header --}}
                        <div class="grid grid-cols-12 gap-2 px-1">
                            <div class="col-span-5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</div>
                            <div class="col-span-2 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 text-center">Cant.</div>
                            <div class="col-span-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Costo Unit.</div>
                            <div class="col-span-2"></div>
                        </div>

                        {{-- Dynamic items --}}
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <div class="col-span-5">
                                    <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" required
                                            class="w-full px-2.5 py-2 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-1 focus:ring-stock-primary/20">
                                        <option value="">Seleccionar...</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->nombre }} {{ $product->tamaño }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" :name="'items[' + index + '][cantidad]'" x-model="item.cantidad"
                                           min="1" required
                                           class="w-full px-2.5 py-2 text-[12px] text-center border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-1 focus:ring-stock-primary/20">
                                </div>
                                <div class="col-span-3">
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] text-gray-400">$</span>
                                        <input type="number" :name="'items[' + index + '][costo_unitario]'" x-model="item.costo_unitario"
                                               step="0.01" min="0" required placeholder="0.00"
                                               class="w-full pl-6 pr-2.5 py-2 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-1 focus:ring-stock-primary/20">
                                    </div>
                                </div>
                                <div class="col-span-2 flex items-center justify-end gap-1">
                                    <button type="button" @click="removeItem(index)"
                                            class="w-6 h-6 rounded-full border border-gray-200 text-gray-400 hover:border-red-300 hover:text-red-500 flex items-center justify-center transition-colors text-[14px] leading-none">×</button>
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="addItem()"
                                class="flex items-center gap-1.5 text-[12px] font-medium text-stock-primary hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar producto
                        </button>

                        {{-- Monto comparison panel --}}
                        <div :class="diffOk() ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'"
                             class="rounded-lg border p-3.5 space-y-2 transition-colors duration-150">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-500">Monto registrado</span>
                                <span class="text-[12px] font-semibold text-gray-800">${{ number_format($receipt->monto_pagado, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-500">Total calculado</span>
                                <span class="text-[13px] font-bold"
                                      :class="diffOk() ? 'text-emerald-700' : 'text-red-700'">$<span x-text="total().toFixed(2)">0.00</span></span>
                            </div>
                            <div x-show="!diffOk()" class="flex items-center justify-between pt-2 border-t border-red-200">
                                <span class="text-[11px] font-semibold text-red-600">Diferencia</span>
                                <span class="text-[12px] font-bold text-red-700">$<span x-text="Math.abs(total() - montoPagado).toFixed(2)"></span></span>
                            </div>
                            <p x-show="diffOk()"  class="text-[10px] font-medium text-emerald-600">Montos coinciden — listo para aprobar</p>
                            <p x-show="!diffOk()" class="text-[10px] font-medium text-red-600">Los montos deben coincidir exactamente</p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">Notas de aprobación (opcional)</label>
                            <textarea name="notas_aprobacion" rows="2" placeholder="Observaciones al aprobar..."
                                      class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg resize-none focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition"></textarea>
                        </div>

                    </div>
                    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] text-gray-400">Se generarán movimientos de inventario automáticamente</span>
                        <button type="submit"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[13px] font-semibold rounded-lg transition-colors">
                            Aprobar y actualizar stock
                        </button>
                    </div>
                </form>

                {{-- Reject section --}}
                <div class="border-t border-gray-100 px-5 py-4"
                     x-data="{ rejecting: false }">
                    <button @click="rejecting = !rejecting" type="button"
                            class="text-[12px] font-medium text-red-500 hover:text-red-700 transition-colors">
                        Rechazar recepción
                    </button>
                    <div x-show="rejecting" x-transition class="mt-3">
                        <form action="{{ route('inventory-receipts.reject', $receipt) }}" method="POST" class="space-y-3">
                            @csrf @method('PATCH')
                            <textarea name="notas_aprobacion" rows="2" required placeholder="Motivo del rechazo (obligatorio)..."
                                      class="w-full px-3.5 py-2.5 text-[13px] border border-red-200 rounded-lg resize-none focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-200 transition"></textarea>
                            <button type="submit"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-[12px] font-semibold rounded-lg transition-colors">
                                Confirmar rechazo
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            @else
            {{-- Approved: show items --}}
            @if($receipt->items->count() > 0)
            <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]">
                <div class="px-5 py-3.5 border-b border-gray-100">
                    <h2 class="text-[13px] font-semibold text-gray-800">Productos ingresados al inventario</h2>
                </div>
                <table class="w-full text-[13px]">
                    <thead class="border-b border-gray-100">
                        <tr>
                            <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                            <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Cantidad</th>
                            <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Costo Unit.</th>
                            <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($receipt->items as $item)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $item->product?->nombre ?? '—' }} <span class="text-gray-400 font-normal">{{ $item->product?->tamaño }}</span></td>
                            <td class="px-5 py-3 text-center text-gray-700">{{ $item->cantidad }}</td>
                            <td class="px-5 py-3 text-right text-gray-600">${{ number_format($item->costo_unitario, 2) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">${{ number_format($item->subtotal(), 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-[12px] font-semibold text-gray-600 text-right">Total ingresado</td>
                            <td class="px-5 py-3 text-right text-[14px] font-bold text-gray-900">${{ number_format($receipt->items->sum(fn($i) => $i->subtotal()), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
            @endif

        </div>
    </div>

</div>
@endsection
