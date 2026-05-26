@extends('layouts.app')
@section('title', 'Confirmar Pago')
@section('content')

<div class="max-w-lg">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('sede-payments.pending') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Pagos pendientes</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Confirmar pago</h1>
    </div>

    {{-- Allocation summary --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] px-5 py-4 mb-5">
        <h3 class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 mb-3">Detalle del aporte</h3>
        <dl class="space-y-2.5">
            <div class="flex justify-between">
                <dt class="text-[13px] text-gray-500">Proveedor</dt>
                <dd class="text-[13px] font-semibold text-gray-800">{{ $allocation->receipt->supplier_name }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[13px] text-gray-500">Recepción</dt>
                <dd class="text-[13px] text-gray-600">#{{ $allocation->receipt->id }} · {{ $allocation->receipt->sede?->nombre }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[13px] text-gray-500">Registrada por</dt>
                <dd class="text-[13px] text-gray-600">{{ $allocation->receipt->user?->name }}</dd>
            </div>
            <div class="flex justify-between pt-1 border-t border-gray-100">
                <dt class="text-[14px] font-semibold text-gray-700">Monto a descontar de tu caja</dt>
                <dd class="text-[18px] font-bold text-amber-700 num">{{ formatCurrency($allocation->amount) }}</dd>
            </div>
        </dl>
    </div>

    {{-- Confirmation form --}}
    <form action="{{ route('sede-payments.confirm', $allocation) }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]">
        @csrf

        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-[13px] font-semibold text-gray-800">Evidencia de pago</h2>
            <p class="text-[11px] text-gray-400 mt-0.5">Sube el comprobante o foto del depósito realizado</p>
        </div>

        <div class="px-6 py-5 space-y-5">

            @if($errors->any())
            <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-[12px] text-red-700">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            {{-- Evidence upload --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Comprobante / Foto <span class="text-red-400">*</span>
                </label>
                <div x-data="{ fileName: '' }"
                     class="relative border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-gray-300 transition-colors cursor-pointer @error('evidence_file') border-red-300 @enderror"
                     @click="$refs.evidenceInput.click()">
                    <input type="file" name="evidence_file" x-ref="evidenceInput"
                           accept=".pdf,.jpg,.jpeg,.png,.webp"
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
                @error('evidence_file')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Notas adicionales
                </label>
                <textarea name="notes" rows="2" placeholder="Número de referencia, banco, observaciones..."
                          class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg resize-none
                                 focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition">{{ old('notes') }}</textarea>
            </div>

            {{-- Warning --}}
            <div class="flex items-start gap-2.5 px-4 py-3 bg-amber-50 border border-amber-200/80 rounded-xl">
                <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-[12px] text-amber-700">
                    Al confirmar, se descontarán <strong>{{ formatCurrency($allocation->amount) }}</strong> de tu caja activa inmediatamente. Esta acción no se puede deshacer.
                </p>
            </div>

        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
            <a href="{{ route('sede-payments.pending') }}"
               class="px-4 py-2 text-[13px] text-gray-600 hover:text-gray-800 transition-colors">Cancelar</a>
            <button type="submit"
                    class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-[13px] font-semibold rounded-lg transition-colors">
                Confirmar y descontar de caja
            </button>
        </div>
    </form>

</div>
@endsection
