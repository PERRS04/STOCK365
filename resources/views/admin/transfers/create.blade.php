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
    @if($errors->has('from') || $errors->has('to') || $errors->has('to_sede_id') || $errors->has('to_almacen_id'))
    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-[13px] text-red-700">
        @foreach(['from','to','to_sede_id','to_almacen_id'] as $key)
            @error($key)<p>{{ $message }}</p>@enderror
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('transfers.store') }}"
          x-data="{
              fromType:      '{{ old('from_almacen_id', request('from_almacen_id')) ? 'almacen' : 'sede' }}',
              toType:        '{{ old('to_almacen_id')   ? 'almacen' : 'sede' }}',
              fromSedeId:    '{{ old('from_sede_id',    '') }}',
              fromAlmacenId: '{{ old('from_almacen_id', request('from_almacen_id', '')) }}',
              toSedeId:      '{{ old('to_sede_id',      '') }}',
              toAlmacenId:   '{{ old('to_almacen_id',   '') }}',
              items: [{ product_id: '', cantidad: 1 }],
              addItem()    { this.items.push({ product_id: '', cantidad: 1 }); },
              removeItem(i){ if (this.items.length > 1) this.items.splice(i, 1); }
          }"
          class="space-y-5">
        @csrf

        {{-- Hidden location inputs — always submitted, value cleared when inactive --}}
        <input type="hidden" name="from_sede_id"    :value="fromType === 'sede'    ? fromSedeId    : ''">
        <input type="hidden" name="from_almacen_id" :value="fromType === 'almacen' ? fromAlmacenId : ''">
        <input type="hidden" name="to_sede_id"      :value="toType   === 'sede'    ? toSedeId      : ''">
        <input type="hidden" name="to_almacen_id"   :value="toType   === 'almacen' ? toAlmacenId   : ''">

        {{-- Route --}}
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-5 grid grid-cols-2 gap-4">

            {{-- Origin --}}
            <div class="space-y-2">
                <label class="block text-[12px] font-medium text-gray-700">Origen *</label>
                <div class="flex gap-1.5">
                    <button type="button"
                            @click="fromType = 'sede'"
                            :class="fromType === 'sede' ? 'bg-stock-primary text-white border-stock-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="flex-1 px-3 py-1.5 text-[12px] font-medium rounded-lg border transition">
                        Sede
                    </button>
                    <button type="button"
                            @click="fromType = 'almacen'"
                            :class="fromType === 'almacen' ? 'bg-stock-primary text-white border-stock-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="flex-1 px-3 py-1.5 text-[12px] font-medium rounded-lg border transition">
                        Almacén
                    </button>
                </div>
                <div x-show="fromType === 'sede'">
                    <select x-model="fromSedeId"
                            class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                        <option value="">— Seleccionar sede —</option>
                        @foreach($sedes as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="fromType === 'almacen'">
                    <select x-model="fromAlmacenId"
                            class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                        <option value="">— Seleccionar almacén —</option>
                        @foreach($almacenes as $a)
                            <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Destination --}}
            <div class="space-y-2">
                <label class="block text-[12px] font-medium text-gray-700">Destino *</label>
                <div class="flex gap-1.5">
                    <button type="button"
                            @click="toType = 'sede'"
                            :class="toType === 'sede' ? 'bg-stock-primary text-white border-stock-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="flex-1 px-3 py-1.5 text-[12px] font-medium rounded-lg border transition">
                        Sede
                    </button>
                    <button type="button"
                            @click="toType = 'almacen'"
                            :class="toType === 'almacen' ? 'bg-stock-primary text-white border-stock-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="flex-1 px-3 py-1.5 text-[12px] font-medium rounded-lg border transition">
                        Almacén
                    </button>
                </div>
                <div x-show="toType === 'sede'">
                    <select x-model="toSedeId"
                            class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                        <option value="">— Seleccionar sede —</option>
                        @foreach($sedes as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="toType === 'almacen'">
                    <select x-model="toAlmacenId"
                            class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                        <option value="">— Seleccionar almacén —</option>
                        @foreach($almacenes as $a)
                            <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                        @endforeach
                    </select>
                </div>
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
