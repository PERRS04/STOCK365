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

        <div class="px-6 py-5 space-y-5" x-data="receiptForm()">

            {{-- Proveedor --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Proveedor <span class="text-red-400">*</span>
                </label>

                <div x-data="providerSelect()"
                     class="relative"
                     @click.outside="close()">

                    <div class="relative">
                        <input type="text"
                               x-ref="input"
                               x-model="query"
                               @focus="isOpen = true"
                               @input="onInput()"
                               @keydown.escape.prevent="close()"
                               @keydown.arrow-down.prevent="moveDown()"
                               @keydown.arrow-up.prevent="moveUp()"
                               @keydown.enter.prevent="confirm()"
                               placeholder="Buscar proveedor..."
                               autocomplete="off"
                               class="w-full px-3.5 py-2.5 pr-9 text-[13px] border rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition @error('provider_id') border-red-300 @else border-gray-200 @enderror">

                        <button type="button"
                                x-show="selectedId"
                                @click.prevent="clear()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <svg x-show="!selectedId"
                             class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 pointer-events-none"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <input type="hidden" name="provider_id" :value="selectedId">

                    <div x-show="isOpen && filtered.length > 0"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                        <template x-for="(provider, i) in filtered" :key="provider.id">
                            <button type="button"
                                    @click="select(provider)"
                                    :class="{
                                        'bg-blue-50': highlightedIndex === i,
                                        'bg-gray-50': selectedId === provider.id && highlightedIndex !== i
                                    }"
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 text-[13px] text-left hover:bg-gray-50 transition-colors">
                                <span x-text="provider.nombre"
                                      :class="selectedId === provider.id ? 'font-semibold text-gray-900' : 'text-gray-700'"></span>
                                <svg x-show="selectedId === provider.id"
                                     class="w-3.5 h-3.5 text-stock-primary shrink-0"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>
                    </div>

                </div>

                @error('provider_id')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Monto total --}}
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500 mb-1.5">
                    Monto Total Pagado (USD) <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-gray-400 font-medium">$</span>
                    <input type="number" name="monto_pagado" x-model.number="total"
                           step="0.01" min="0.01" placeholder="0.00"
                           class="w-full pl-8 pr-3.5 py-2.5 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:border-stock-primary focus:ring-2 focus:ring-stock-primary/20 transition @error('monto_pagado') border-red-300 @enderror">
                </div>
                @error('monto_pagado')
                    <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Apoyo de otras sedes (optional) --}}
            @if($sedes->count() > 0)
            <div class="border border-dashed border-gray-200 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50/70">
                    <div>
                        <p class="text-[12px] font-semibold text-gray-700">Apoyo de otra sede</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Opcional · Si otra sede cubre parte del pago</p>
                    </div>
                    <button type="button" @click="addRow()"
                            class="flex items-center gap-1.5 text-[11px] font-semibold text-stock-primary hover:text-blue-800 transition-colors px-2 py-1 rounded-md hover:bg-blue-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Añadir sede
                    </button>
                </div>

                <template x-if="allocs.length > 0">
                    <div class="px-4 pb-4 pt-3 space-y-2">

                        <template x-for="(alloc, i) in allocs" :key="i">
                            <div class="flex items-center gap-2">
                                <select :name="`allocations[${i}][sede_id]`" x-model="alloc.sede_id"
                                        class="flex-1 px-3 py-2 text-[12px] border border-gray-200 rounded-lg
                                               focus:outline-none focus:border-stock-primary focus:ring-1 focus:ring-stock-primary/20 transition bg-white">
                                    <option value="">Seleccionar sede...</option>
                                    @foreach($sedes as $sede)
                                    <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="relative w-28 shrink-0">
                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[12px] text-gray-400">$</span>
                                    <input type="number" :name="`allocations[${i}][monto]`"
                                           x-model.number="alloc.monto"
                                           step="0.01" min="0.01" placeholder="0.00"
                                           class="w-full pl-6 pr-2 py-2 text-[12px] border border-gray-200 rounded-lg
                                                  focus:outline-none focus:border-stock-primary focus:ring-1 focus:ring-stock-primary/20 transition">
                                </div>
                                <button type="button" @click="removeRow(i)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-300
                                               hover:text-red-400 hover:bg-red-50 transition-colors shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>

                        {{-- Summary --}}
                        <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                            <div class="flex items-center justify-between text-[12px]">
                                <span class="text-gray-500">Aportes externos</span>
                                <span class="text-gray-700 font-medium num">$<span x-text="extTotal.toFixed(2)"></span></span>
                            </div>
                            <div class="flex items-center justify-between text-[13px] font-semibold">
                                <span class="text-gray-700">Esta sede paga</span>
                                <span :class="local < 0 ? 'text-red-600' : 'text-stock-primary'" class="num">
                                    $<span x-text="local.toFixed(2)"></span>
                                </span>
                            </div>
                            <p x-show="local < 0" class="text-[11px] text-red-500">
                                Los aportes externos superan el monto total.
                            </p>
                        </div>

                    </div>
                </template>
            </div>
            @endif

            @error('allocations')
                <p class="text-[11px] text-red-500 -mt-3">{{ $message }}</p>
            @enderror

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

@push('scripts')
<script>
function providerSelect() {
    const oldId = {{ old('provider_id') ? (int) old('provider_id') : 'null' }};
    const allProviders = @json($providers->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre]));
    const initial = oldId ? allProviders.find(p => p.id === oldId) : null;

    return {
        query: initial ? initial.nombre : '',
        selectedId: initial ? initial.id : null,
        isOpen: false,
        highlightedIndex: -1,
        providers: allProviders,

        get filtered() {
            const q = this.query.toLowerCase().trim();
            if (!q) return this.providers;
            const sel = this.providers.find(p => p.id === this.selectedId);
            if (sel && sel.nombre.toLowerCase() === q) return this.providers;
            return this.providers.filter(p => p.nombre.toLowerCase().includes(q));
        },

        onInput() {
            this.selectedId = null;
            this.isOpen = true;
            this.highlightedIndex = -1;
        },

        select(provider) {
            this.selectedId = provider.id;
            this.query = provider.nombre;
            this.isOpen = false;
            this.highlightedIndex = -1;
        },

        clear() {
            this.selectedId = null;
            this.query = '';
            this.isOpen = false;
            this.$nextTick(() => this.$refs.input.focus());
        },

        close() {
            this.isOpen = false;
            if (!this.selectedId) {
                this.query = '';
            } else {
                const sel = this.providers.find(p => p.id === this.selectedId);
                if (sel) this.query = sel.nombre;
            }
        },

        moveDown() {
            if (!this.isOpen) { this.isOpen = true; return; }
            if (this.highlightedIndex < this.filtered.length - 1) this.highlightedIndex++;
        },

        moveUp() {
            if (this.highlightedIndex > 0) this.highlightedIndex--;
        },

        confirm() {
            if (this.highlightedIndex >= 0 && this.filtered[this.highlightedIndex]) {
                this.select(this.filtered[this.highlightedIndex]);
            }
        }
    }
}

function receiptForm() {
    const oldAllocs = @json(
        collect(old('allocations', []))
            ->filter(fn($a) => !empty($a['sede_id']))
            ->map(fn($a) => ['sede_id' => (string)($a['sede_id'] ?? ''), 'monto' => isset($a['monto']) ? (float)$a['monto'] : null])
            ->values()
    );

    return {
        total: {{ old('monto_pagado') ? (float)old('monto_pagado') : 0 }},
        allocs: oldAllocs.length ? oldAllocs : [],

        get extTotal() {
            return this.allocs.reduce((s, r) => s + (parseFloat(r.monto) || 0), 0);
        },
        get local() {
            return Math.max(0, (parseFloat(this.total) || 0) - this.extTotal);
        },

        addRow() {
            this.allocs.push({ sede_id: '', monto: null });
        },
        removeRow(i) {
            this.allocs.splice(i, 1);
        },
    };
}
</script>
@endpush

@endsection
