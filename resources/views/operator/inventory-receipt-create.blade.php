@extends('layouts.app')
@section('title', 'Registrar Recepción de Mercancía')
@section('content')

<div class="max-w-xl">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dashboard') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Dashboard</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Recepción de Mercancía</h1>
    </div>

    {{-- Info banner --}}
    <div class="flex items-start gap-3 bg-blue-50 border border-blue-200/80 rounded-xl px-4 py-3.5 mb-6">
        <svg class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-[12px] font-semibold text-blue-800">Registro de pago y recepción física</p>
            <p class="text-[11px] text-blue-600 mt-0.5">El ingreso al inventario será confirmado por supervisión tras revisar la factura. Solo registra el pago y adjunta el documento.</p>
        </div>
    </div>

    <form action="{{ route('inventory-receipts.store') }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]">
        @csrf

        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-[13px] font-semibold text-gray-800">Datos de la recepción</h2>
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Proveedor --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Proveedor / Empresa <span class="text-red-400">*</span>
                </label>
                <input type="text" name="supplier_name" value="{{ old('supplier_name') }}"
                       placeholder="Ej: Cervecería Nacional, Ambev Ecuador..."
                       class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition @error('supplier_name') border-red-300 @enderror">
                @error('supplier_name')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Monto --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Monto Pagado (USD) <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-gray-400 font-medium">$</span>
                    <input type="number" name="monto_pagado" value="{{ old('monto_pagado') }}"
                           step="0.01" min="0.01" placeholder="0.00"
                           class="w-full pl-8 pr-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition @error('monto_pagado') border-red-300 @enderror">
                </div>
                @error('monto_pagado')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Observaciones --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Observaciones
                </label>
                <textarea name="observaciones" rows="3" placeholder="Productos recibidos, cantidades aproximadas, notas..."
                          class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg resize-none focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition">{{ old('observaciones') }}</textarea>
            </div>

            {{-- Factura --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Factura / Comprobante
                </label>
                <div x-data="{ fileName: '' }"
                     class="relative border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-gray-300 transition-colors cursor-pointer"
                     @click="$refs.fileInput.click()">
                    <input type="file" name="invoice_file" x-ref="fileInput" accept=".pdf,.jpg,.jpeg,.png,.webp"
                           class="hidden" @change="fileName = $event.target.files[0]?.name || ''">
                    <template x-if="!fileName">
                        <div>
                            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-[12px] text-gray-500 font-medium">Toca para subir foto o PDF</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">JPG, PNG, PDF — máx. 5 MB</p>
                        </div>
                    </template>
                    <template x-if="fileName">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-[12px] font-medium text-gray-700" x-text="fileName"></p>
                        </div>
                    </template>
                </div>
                @error('invoice_file')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Sede (readonly for operator) --}}
            <div class="flex items-center justify-between py-2.5 px-3.5 bg-gray-50 rounded-lg">
                <span class="text-[12px] text-gray-500">Sede</span>
                <span class="text-[13px] font-semibold text-gray-800">{{ auth()->user()->sede->nombre }}</span>
            </div>

        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-[13px] text-gray-600 hover:text-gray-800 transition-colors">Cancelar</a>
            <button type="submit"
                    class="px-5 py-2 bg-stock-primary hover:bg-blue-800 text-white text-[13px] font-semibold rounded-lg transition-colors">
                Registrar Recepción
            </button>
        </div>
    </form>

</div>
@endsection
