@extends('layouts.app')
@section('title', 'Registrar Cortesía')
@section('content')

<div class="max-w-xl">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dashboard') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Dashboard</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Registrar Cortesía</h1>
    </div>

    {{-- Info banner --}}
    <div class="flex items-start gap-3 bg-blue-50 border border-blue-200/80 rounded-xl px-4 py-3.5 mb-6">
        <svg class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-[12px] font-semibold text-blue-800">Requiere aprobación de supervisión</p>
            <p class="text-[11px] text-blue-600 mt-0.5">El stock <strong>no se descuenta</strong> hasta que supervisión apruebe. La foto es <strong>obligatoria</strong>. Sin foto, no se puede registrar.</p>
        </div>
    </div>

    <form action="{{ route('courtesies.store') }}" method="POST" enctype="multipart/form-data"
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
            <h2 class="text-[13px] font-semibold text-gray-800">Datos de la cortesía</h2>
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Producto --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Producto <span class="text-red-400">*</span>
                </label>
                <select name="product_id" required
                        class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 bg-white transition @error('product_id') border-red-300 @enderror">
                    <option value="">— Seleccionar producto —</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->nombre }} · {{ $product->marca }}{{ $product->tamaño ? ' · '.$product->tamaño : '' }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cantidad + Tipo --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                        Cantidad <span class="text-red-400">*</span>
                    </label>
                    <input type="number" name="quantity" min="1" required value="{{ old('quantity', 1) }}"
                           class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition @error('quantity') border-red-300 @enderror"/>
                    @error('quantity')
                        <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                        Tipo <span class="text-red-400">*</span>
                    </label>
                    <select name="tipo" required
                            class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 bg-white transition @error('tipo') border-red-300 @enderror">
                        <option value="">— Seleccionar —</option>
                        <option value="cumpleaños"    {{ old('tipo') === 'cumpleaños'    ? 'selected' : '' }}>Cumpleaños</option>
                        <option value="apostador_vip" {{ old('tipo') === 'apostador_vip' ? 'selected' : '' }}>Apostador VIP</option>
                        <option value="promocion"     {{ old('tipo') === 'promocion'     ? 'selected' : '' }}>Promoción</option>
                        <option value="gerencia"      {{ old('tipo') === 'gerencia'      ? 'selected' : '' }}>Gerencia</option>
                        <option value="incidencia"    {{ old('tipo') === 'incidencia'    ? 'selected' : '' }}>Incidencia</option>
                        <option value="otro"          {{ old('tipo') === 'otro'          ? 'selected' : '' }}>Otro</option>
                    </select>
                    @error('tipo')
                        <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Motivo --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Motivo / Descripción <span class="text-red-400">*</span>
                </label>
                <input type="text" name="motivo" required maxlength="500" value="{{ old('motivo') }}"
                       placeholder="Ej: Mesa 7 celebra cumpleaños, cliente frecuente Carlos R."
                       class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition @error('motivo') border-red-300 @enderror"/>
                @error('motivo')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cliente nombre (opcional) --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Nombre del cliente <span class="text-[11px] font-normal normal-case tracking-normal text-gray-400">(opcional)</span>
                </label>
                <input type="text" name="cliente_nombre" maxlength="255" value="{{ old('cliente_nombre') }}"
                       placeholder="Ej: Carlos Rodríguez"
                       class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition"/>
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

            {{-- Foto evidencia (OBLIGATORIA) --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Foto evidencia <span class="text-red-400">*</span>
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
