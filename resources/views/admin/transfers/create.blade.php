@extends('layouts.app')
@section('title', 'Nueva Transferencia')
@section('content')

<div class="max-w-3xl space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('transfers.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Transferencias</a>
        <span class="text-gray-200">/</span>
        <h1 class="text-[18px] font-semibold text-gray-900">Nueva Transferencia</h1>
    </div>

    @if(session('error'))
    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-[13px] text-red-700">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('transfers.store') }}"
          x-data="{
              items: [{ product_id: '', cantidad: 1 }],
              addItem() { this.items.push({ product_id: '', cantidad: 1 }); },
              removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); }
          }"
          class="space-y-5">
        @csrf

        {{-- Route --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5 grid grid-cols-2 gap-4">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Sede Origen *</label>
                <select name="from_sede_id" required
                        class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary @error('from_sede_id') border-red-300 @enderror">
                    <option value="">— Seleccionar —</option>
                    @foreach($sedes as $s)
                        <option value="{{ $s->id }}" {{ old('from_sede_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
                @error('from_sede_id') <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Sede Destino *</label>
                <select name="to_sede_id" required
                        class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary @error('to_sede_id') border-red-300 @enderror">
                    <option value="">— Seleccionar —</option>
                    @foreach($sedes as $s)
                        <option value="{{ $s->id }}" {{ old('to_sede_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
                @error('to_sede_id') <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 mb-1.5">Motivo</label>
                <input type="text" name="motivo" value="{{ old('motivo') }}" placeholder="Redistribución de stock, solicitud sede, etc."
                       class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
            </div>
        </div>

        {{-- Products --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-[13px] font-semibold text-gray-900">Productos a transferir</h2>
                <button type="button" @click="addItem()"
                        class="text-[12px] font-medium text-stock-primary hover:underline">+ Agregar producto</button>
            </div>

            <div class="p-5 space-y-3">
                <template x-for="(item, i) in items" :key="i">
                    <div class="flex gap-3 items-start">
                        <div class="flex-1">
                            <label class="block text-[11px] text-gray-500 mb-1">Producto</label>
                            <select :name="`items[${i}][product_id]`" x-model="item.product_id" required
                                    class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                                <option value="">— Seleccionar —</option>
                                @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}{{ $p->marca ? ' · '.$p->marca : '' }}{{ $p->tamaño ? ' · '.$p->tamaño : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-28">
                            <label class="block text-[11px] text-gray-500 mb-1">Cantidad</label>
                            <input type="number" :name="`items[${i}][cantidad]`" x-model="item.cantidad"
                                   min="1" required
                                   class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary text-center">
                        </div>
                        <div class="pt-5">
                            <button type="button" @click="removeItem(i)"
                                    class="p-2 text-gray-400 hover:text-red-500 transition rounded-lg hover:bg-red-50"
                                    x-show="items.length > 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-5 py-4 bg-amber-50 border-t border-amber-100">
                <p class="text-[12px] text-amber-700">
                    <strong>Importante:</strong> El stock no se modifica hasta que un supervisor o boss apruebe la transferencia.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('transfers.index') }}" class="px-4 py-2 text-[13px] text-gray-600 hover:text-gray-900 transition">Cancelar</a>
            <button type="submit" class="px-5 py-2 bg-stock-primary text-white text-[13px] font-medium rounded-lg hover:bg-stock-primary/90 transition">
                Solicitar Transferencia
            </button>
        </div>
    </form>

</div>
@endsection
