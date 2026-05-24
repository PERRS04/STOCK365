@extends('layouts.app')
@section('title', 'Registrar Movimiento de Caja')
@section('content')

<div class="max-w-xl">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dashboard') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Dashboard</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Movimiento de Caja</h1>
    </div>

    {{-- Info banner --}}
    <div class="flex items-start gap-3 bg-blue-50 border border-blue-200/80 rounded-xl px-4 py-3.5 mb-6">
        <svg class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-[12px] font-semibold text-blue-800">Requiere aprobación de supervisión</p>
            <p class="text-[11px] text-blue-600 mt-0.5">El movimiento <strong>no afecta el cierre</strong> hasta que supervisión lo apruebe. La foto del comprobante es <strong>obligatoria</strong>.</p>
        </div>
    </div>

    @if($activeSession)
    <div class="flex items-center gap-2.5 px-4 py-2.5 bg-emerald-50 border border-emerald-200 rounded-xl mb-6">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
        <p class="text-[12px] text-emerald-700">Caja abierta · Sesión #{{ $activeSession->id }} · {{ $activeSession->opened_at->format('H:i') }}</p>
    </div>
    @else
    <div class="flex items-center gap-2.5 px-4 py-2.5 bg-amber-50 border border-amber-200 rounded-xl mb-6">
        <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-[12px] text-amber-700">Sin sesión de caja activa. El movimiento quedará sin sesión asociada.</p>
    </div>
    @endif

    <form action="{{ route('cash-movements.store') }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]"
          x-data="{
              fileName: '',
              preview: null,
              handleFile(file) {
                  if (!file) return;
                  this.fileName = file.name;
                  const reader = new FileReader();
                  reader.onload = e => this.preview = e.target.result;
                  reader.readAsDataURL(file);
              }
          }">
        @csrf

        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-[13px] font-semibold text-gray-800">Datos del movimiento</h2>
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Tipo + Monto --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                        Tipo de movimiento <span class="text-red-400">*</span>
                    </label>
                    <select name="type" required
                            class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 bg-white transition @error('type') border-red-300 @enderror">
                        <option value="">— Seleccionar —</option>
                        <optgroup label="Entradas">
                            <option value="deposito"     {{ old('type') === 'deposito'     ? 'selected' : '' }}>Depósito</option>
                            <option value="cambio"       {{ old('type') === 'cambio'       ? 'selected' : '' }}>Cambio / Fondo</option>
                        </optgroup>
                        <optgroup label="Salidas">
                            <option value="retiro"          {{ old('type') === 'retiro'          ? 'selected' : '' }}>Retiro</option>
                            <option value="pago_proveedor"  {{ old('type') === 'pago_proveedor'  ? 'selected' : '' }}>Pago Proveedor</option>
                            <option value="envio_boveda"    {{ old('type') === 'envio_boveda'    ? 'selected' : '' }}>Envío Bóveda</option>
                            <option value="gasto_operativo" {{ old('type') === 'gasto_operativo' ? 'selected' : '' }}>Gasto Operativo</option>
                        </optgroup>
                        <optgroup label="Ajustes">
                            <option value="ajuste"     {{ old('type') === 'ajuste'     ? 'selected' : '' }}>Ajuste</option>
                            <option value="diferencia" {{ old('type') === 'diferencia' ? 'selected' : '' }}>Diferencia</option>
                        </optgroup>
                    </select>
                    @error('type')
                        <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                        Monto (USD) <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-gray-400 font-medium">$</span>
                        <input type="number" name="amount" value="{{ old('amount') }}"
                               step="0.01" min="0.01" placeholder="0.00"
                               class="w-full pl-8 pr-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition @error('amount') border-red-300 @enderror"/>
                    </div>
                    @error('amount')
                        <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Motivo --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Motivo <span class="text-red-400">*</span>
                </label>
                <input type="text" name="motivo" required maxlength="500" value="{{ old('motivo') }}"
                       placeholder="Ej: Pago factura proveedor Ambev, retiro para banco..."
                       class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition @error('motivo') border-red-300 @enderror"/>
                @error('motivo')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Observaciones (opcional) --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Observaciones <span class="text-[11px] font-normal normal-case tracking-normal text-gray-400">(opcional)</span>
                </label>
                <textarea name="observaciones" rows="2" maxlength="1000"
                          placeholder="Detalles adicionales..."
                          class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg resize-none focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition">{{ old('observaciones') }}</textarea>
            </div>

            {{-- Foto comprobante (OBLIGATORIA) --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Foto del comprobante <span class="text-red-400">*</span>
                </label>

                <input type="file" name="attachment" x-ref="fileInput" accept="image/jpeg,image/png,image/webp" required
                       class="hidden" @change="handleFile($event.target.files[0])"/>

                <div @click="$refs.fileInput.click()"
                     @dragover.prevent
                     @drop.prevent="
                         const f = $event.dataTransfer.files[0];
                         if (f) {
                             const dt = new DataTransfer();
                             dt.items.add(f);
                             $refs.fileInput.files = dt.files;
                             handleFile(f);
                         }
                     "
                     class="cursor-pointer border-2 border-dashed rounded-xl p-6 text-center transition-colors"
                     :class="preview ? 'border-emerald-300 bg-emerald-50/40' : 'border-gray-200 hover:border-stock-primary/40 hover:bg-blue-50/20'">

                    <template x-if="!preview">
                        <div>
                            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-[12px] text-gray-500 font-medium">Arrastra la foto o <span class="text-stock-primary">haz clic para seleccionar</span></p>
                            <p class="text-[11px] text-gray-400 mt-0.5">JPG, PNG, WebP — máx. 5 MB</p>
                        </div>
                    </template>

                    <template x-if="preview">
                        <div class="flex items-center gap-3 text-left">
                            <img :src="preview" class="w-16 h-16 rounded-lg object-cover border border-emerald-200 shrink-0"/>
                            <div class="min-w-0">
                                <p class="text-[12px] font-medium text-emerald-700 truncate" x-text="fileName"></p>
                                <p class="text-[11px] text-gray-400 mt-0.5">Toca para cambiar</p>
                            </div>
                            <svg class="w-5 h-5 text-emerald-500 ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </template>
                </div>

                @error('attachment')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Sede (readonly) --}}
            <div class="flex items-center justify-between py-2.5 px-3.5 bg-gray-50 rounded-lg border border-gray-100">
                <span class="text-[12px] text-gray-500">Sede</span>
                <span class="text-[13px] font-semibold text-gray-800">{{ auth()->user()->sede?->nombre ?? '—' }}</span>
            </div>

        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-[13px] text-gray-600 hover:text-gray-800 transition-colors">Cancelar</a>
            <button type="submit"
                    class="px-5 py-2 bg-stock-primary hover:bg-blue-800 text-white text-[13px] font-semibold rounded-lg transition-colors">
                Enviar para aprobación
            </button>
        </div>
    </form>

</div>
@endsection
