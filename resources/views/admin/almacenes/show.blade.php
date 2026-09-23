@extends('layouts.app')
@section('title', $almacen->nombre)
@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <a href="{{ route('almacenes.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Almacenes</a>
                <span class="text-gray-200">/</span>
                <h1 class="text-[18px] font-semibold text-gray-900">{{ $almacen->nombre }}</h1>
                @if($almacen->activo)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full border text-[11px] font-semibold bg-emerald-50 text-emerald-700 border-emerald-200">Activo</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full border text-[11px] font-semibold bg-gray-100 text-gray-500 border-gray-200">Inactivo</span>
                @endif
            </div>
            @if($almacen->descripcion)
                <p class="text-[13px] text-gray-500">{{ $almacen->descripcion }}</p>
            @endif
            @if($lastActivity)
                <p class="text-[11px] text-gray-400">Última actividad: {{ \Carbon\Carbon::parse($lastActivity)->diffForHumans() }}</p>
            @endif
        </div>
        <a href="{{ route('almacenes.edit', $almacen) }}"
           class="px-3 py-1.5 text-[12px] text-gray-500 border border-gray-200 rounded-lg hover:border-gray-300 hover:text-gray-700 transition shrink-0">
            Editar
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[13px] text-emerald-700">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-[13px] text-red-700">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl text-[13px] text-blue-700">{{ session('info') }}</div>
    @endif

    {{-- Action buttons --}}
    @if($almacen->activo)
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('almacenes.entry.create', $almacen) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-stock-primary text-white text-[13px] font-semibold rounded-xl hover:bg-stock-primary/90 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Entrada de mercancía
        </a>
        <a href="{{ route('transfers.create', ['from_almacen_id' => $almacen->id]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gray-700 text-[13px] font-semibold rounded-xl border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Transferir mercancía
        </a>
        <a href="{{ route('almacenes.movements', $almacen) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gray-700 text-[13px] font-semibold rounded-xl border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Ver movimientos
        </a>
        <a href="{{ route('inventory.bulk-load', ['loc_type' => 'almacen', 'almacen_id' => $almacen->id]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gray-700 text-[13px] font-semibold rounded-xl border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Carga masiva
        </a>
    </div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Productos con stock</p>
            <p class="text-[26px] font-bold text-gray-900 leading-none">{{ $totalProductosConStock }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Unidades totales</p>
            <p class="text-[26px] font-bold text-gray-900 leading-none">{{ number_format($totalUnidades) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Valor inventario (costo)</p>
            <p class="text-[22px] font-bold text-gray-900 leading-none">${{ number_format($valorInventario, 2) }}</p>
            <p class="text-[10px] text-gray-400 mt-0.5">precio_compra promedio ponderado</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1">Movimientos (30 días)</p>
            <p class="text-[26px] font-bold text-gray-900 leading-none">{{ number_format($movimientosRecientes) }}</p>
        </div>
    </div>

    {{-- Stock table --}}
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden"
         x-data="{ adjustModal: false, adjustProductId: null, adjustProductName: '', adjustCurrentStock: 0, adjustNewStock: 0, adjustMotivo: '' }">

        {{-- Table toolbar --}}
        <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-center gap-3">
            <h2 class="text-[13px] font-semibold text-gray-900 mr-1">Stock actual</h2>

            {{-- Search --}}
            <form method="GET" action="{{ route('almacenes.show', $almacen) }}" class="flex items-center gap-2">
                <input type="hidden" name="stock" value="{{ $stockFilter }}">
                <input type="text" name="buscar" value="{{ $search }}"
                       placeholder="Buscar producto…"
                       class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary w-52">
                @if($search)
                <a href="{{ route('almacenes.show', ['almacen' => $almacen->id, 'stock' => $stockFilter]) }}"
                   class="text-[11px] text-gray-400 hover:text-gray-600">✕</a>
                @endif
            </form>

            {{-- Stock filter --}}
            <div class="flex gap-1">
                @foreach(['todos' => 'Todos', 'con_stock' => 'Con stock', 'sin_stock' => 'Sin stock'] as $val => $label)
                <a href="{{ route('almacenes.show', ['almacen' => $almacen->id, 'stock' => $val, 'buscar' => $search]) }}"
                   class="px-3 py-1.5 text-[11px] font-medium rounded-lg border transition
                          {{ $stockFilter === $val ? 'bg-stock-primary text-white border-stock-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>

            <span class="text-[12px] text-gray-400 ml-auto">{{ $inventories->count() }} productos</span>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100 bg-gray-50/50">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Stock actual</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Costo unit.</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Valor</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Último mov.</th>
                    @if($almacen->activo)
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 w-24">Ajustar</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($inventories as $inv)
                @php
                    $costo  = (float) ($inv->product?->precio_compra ?? 0);
                    $valor  = $inv->cantidad_stock * $costo;
                    $lastAt = $lastMovements[$inv->product_id] ?? null;
                @endphp
                <tr class="hover:bg-gray-50/40">
                    <td class="px-5 py-2.5">
                        <p class="font-medium text-gray-900">{{ $inv->product?->nombre }}</p>
                        <p class="text-[11px] text-gray-400">{{ implode(' · ', array_filter([$inv->product?->marca, $inv->product?->tamaño])) }}</p>
                    </td>
                    <td class="px-5 py-2.5 text-right">
                        <span class="font-bold text-[15px] {{ $inv->cantidad_stock <= 0 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $inv->cantidad_stock }}
                        </span>
                    </td>
                    <td class="px-5 py-2.5 text-right text-gray-600 text-[12px]">
                        ${{ number_format($costo, 2) }}
                    </td>
                    <td class="px-5 py-2.5 text-right font-semibold text-gray-800">
                        ${{ number_format($valor, 2) }}
                    </td>
                    <td class="px-5 py-2.5 text-[12px] text-gray-400">
                        {{ $lastAt ? \Carbon\Carbon::parse($lastAt)->format('d/m/Y') : '—' }}
                    </td>
                    @if($almacen->activo)
                    <td class="px-5 py-2.5 text-center">
                        <button type="button"
                                @click="adjustModal = true; adjustProductId = {{ $inv->product_id }}; adjustProductName = @js($inv->product?->nombre ?? ''); adjustCurrentStock = {{ $inv->cantidad_stock }}; adjustNewStock = {{ $inv->cantidad_stock }}; adjustMotivo = ''"
                                class="px-2.5 py-1 text-[11px] font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                            Ajustar
                        </button>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $almacen->activo ? 6 : 5 }}" class="px-5 py-12 text-center text-[13px] text-gray-400">
                        @if($search)
                            Sin resultados para "{{ $search }}"
                        @else
                            Sin registros de inventario para este almacén.
                            @if($almacen->activo)
                                <a href="{{ route('almacenes.entry.create', $almacen) }}" class="text-stock-primary hover:underline ml-1">Registrar primera entrada →</a>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        {{-- Adjustment modal (inline) --}}
        @if($almacen->activo)
        <div x-show="adjustModal"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm"
             @click.self="adjustModal = false"
             style="display:none;">
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-200/80 w-full max-w-sm mx-4 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-[15px] font-semibold text-gray-900">Ajustar inventario</h3>
                    <button type="button" @click="adjustModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-1">
                    <p class="text-[13px] text-gray-600">Producto: <span class="font-semibold text-gray-900" x-text="adjustProductName"></span></p>
                    <p class="text-[13px] text-gray-500">Stock actual en sistema: <span class="font-bold text-gray-900" x-text="adjustCurrentStock"></span></p>
                </div>

                <form :action="'{{ route('almacenes.adjust', $almacen) }}'" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="product_id" :value="adjustProductId">

                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Stock real (conteo físico)</label>
                        <input type="number" name="new_cantidad" x-model.number="adjustNewStock" min="0" required
                               class="w-full px-3 py-2.5 text-[16px] font-bold border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary text-center">
                        <p class="text-[12px] mt-1.5 font-semibold text-center"
                           :class="adjustNewStock > adjustCurrentStock ? 'text-emerald-600' : (adjustNewStock < adjustCurrentStock ? 'text-red-600' : 'text-gray-400')"
                           x-text="adjustNewStock > adjustCurrentStock
                               ? '+' + (adjustNewStock - adjustCurrentStock) + ' unidades'
                               : (adjustNewStock < adjustCurrentStock
                                   ? (adjustNewStock - adjustCurrentStock) + ' unidades'
                                   : 'Sin cambio')">
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Motivo (requerido)</label>
                        <input type="text" name="motivo" x-model="adjustMotivo" required minlength="3"
                               placeholder="Ej: 3 unidades rotas, merma, conteo físico…"
                               class="w-full px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="adjustModal = false"
                                class="flex-1 px-4 py-2 text-[13px] font-medium text-gray-600 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                        <button type="submit"
                                :disabled="!adjustMotivo || adjustMotivo.length < 3"
                                class="flex-1 px-4 py-2 text-[13px] font-semibold bg-stock-primary text-white rounded-lg hover:bg-stock-primary/90 transition disabled:opacity-40 disabled:cursor-not-allowed">
                            Confirmar ajuste
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>

</div>
@endsection
