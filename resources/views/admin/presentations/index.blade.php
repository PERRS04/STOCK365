@extends('layouts.app')

@section('title', 'Precios y Presentaciones — ' . $product->nombre)

@section('content')
<div class="max-w-5xl mx-auto space-y-8 px-4 py-8">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.14em] mb-1">Gestión de precios</p>
            <h1 class="text-[22px] font-black text-gray-900">{{ $product->nombre }}</h1>
            <p class="text-[13px] text-gray-400 mt-1">
                SKU: <span class="font-mono text-gray-600">{{ $product->sku ?? '—' }}</span>
                &nbsp;·&nbsp; Precio base: <span class="font-semibold text-gray-700">${{ number_format($product->precio_venta, 2) }}</span>
            </p>
        </div>
        <a href="{{ route('products.index') }}"
           class="flex items-center gap-2 text-[13px] text-gray-500 hover:text-gray-700 transition px-3 py-2 rounded-lg hover:bg-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Productos
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-[13px] font-medium">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-[13px]">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ── SECTION: Product Sede Prices ───────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-[15px] font-bold text-gray-800">Precio por sede (sin presentación)</h2>
                <p class="text-[12px] text-gray-400 mt-0.5">Sobreescribe el precio base del producto para una sede específica.</p>
            </div>
        </div>

        @if($product->sedePrices->isNotEmpty())
        <table class="w-full text-[13px]">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-3 font-semibold text-gray-500">Sede</th>
                    <th class="text-right px-6 py-3 font-semibold text-gray-500">Precio</th>
                    <th class="text-right px-6 py-3 font-semibold text-gray-500">Estado</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($product->sedePrices as $sp)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-3 font-medium text-gray-800">{{ $sp->sede->nombre ?? '—' }}</td>
                    <td class="px-6 py-3 text-right font-semibold tabular-nums">${{ number_format($sp->precio_venta, 2) }}</td>
                    <td class="px-6 py-3 text-right">
                        <span class="{{ $sp->activo ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400' }} px-2.5 py-1 rounded-full text-[11px] font-semibold">
                            {{ $sp->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <form method="POST" action="{{ route('product-sede-prices.destroy', [$product, $sp]) }}"
                              onsubmit="return confirm('¿Eliminar este precio?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600 text-[12px] font-medium transition">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="px-6 py-4 text-[13px] text-gray-400 italic">No hay precios por sede configurados. El precio base (${{ number_format($product->precio_venta, 2) }}) se aplica en todas las sedes.</p>
        @endif

        {{-- Add sede price form --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
            <p class="text-[12px] font-semibold text-gray-600 mb-3">Agregar precio por sede</p>
            <form method="POST" action="{{ route('product-sede-prices.store', $product) }}" class="flex items-end gap-3 flex-wrap">
                @csrf
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Sede</label>
                    <select name="sede_id" required
                        class="border border-gray-200 rounded-lg px-3 py-2 text-[13px] text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                        <option value="">Seleccionar...</option>
                        @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Precio</label>
                    <input type="number" name="precio_venta" step="0.01" min="0" required placeholder="0.00"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-[13px] w-32 text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                </div>
                <button type="submit"
                    class="h-10 px-4 bg-[#003594] text-white rounded-lg text-[13px] font-semibold hover:bg-[#002470] transition">
                    Guardar
                </button>
            </form>
        </div>
    </div>

    {{-- ── SECTION: Presentations ──────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-[15px] font-bold text-gray-800">Presentaciones</h2>
            <p class="text-[12px] text-gray-400 mt-0.5">Cada presentación agrupa N unidades base. El precio se configura por sede o globalmente.</p>
        </div>

        @forelse($product->presentations as $pres)
        <div class="border-b border-gray-100 last:border-0">
            <div class="px-6 py-4">

                {{-- Presentation header --}}
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div class="flex items-center gap-3">
                        <span class="{{ $pres->activo ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400' }} px-2.5 py-1 rounded-full text-[11px] font-bold">
                            {{ $pres->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                        <div>
                            <span class="text-[15px] font-bold text-gray-800">{{ $pres->nombre }}</span>
                            <span class="text-[12px] text-gray-400 ml-2">× {{ $pres->factor_stock }} uds. base (descuenta {{ $pres->factor_stock }} del inventario por unidad vendida)</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Edit form (inline) --}}
                        <button onclick="document.getElementById('edit-pres-{{ $pres->id }}').classList.toggle('hidden')"
                            class="text-[12px] font-medium text-[#003594] hover:text-[#002470] transition px-2 py-1 rounded hover:bg-blue-50">
                            Editar
                        </button>
                        {{-- Delete --}}
                        <form method="POST" action="{{ route('product-presentations.destroy', [$product, $pres]) }}"
                              onsubmit="return confirm('¿Eliminar o desactivar esta presentación?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-[12px] font-medium text-red-400 hover:text-red-600 transition px-2 py-1 rounded hover:bg-red-50">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Edit form (hidden by default) --}}
                <div id="edit-pres-{{ $pres->id }}" class="hidden mb-4 bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <form method="POST" action="{{ route('product-presentations.update', [$product, $pres]) }}" class="flex flex-wrap items-end gap-3">
                        @csrf @method('PATCH')
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Nombre</label>
                            <input type="text" name="nombre" value="{{ $pres->nombre }}" required maxlength="100"
                                class="border border-gray-200 rounded-lg px-3 py-2 text-[13px] w-40 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Factor (unidades)</label>
                            <input type="number" name="factor_stock" value="{{ $pres->factor_stock }}" min="1" required
                                class="border border-gray-200 rounded-lg px-3 py-2 text-[13px] w-24 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Orden</label>
                            <input type="number" name="sort_order" value="{{ $pres->sort_order }}" min="0"
                                class="border border-gray-200 rounded-lg px-3 py-2 text-[13px] w-20 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                        </div>
                        <div class="flex items-center gap-2 pb-1">
                            <input type="checkbox" name="activo" value="1" id="activo-{{ $pres->id }}" {{ $pres->activo ? 'checked' : '' }}
                                class="w-4 h-4 text-[#003594] rounded">
                            <label for="activo-{{ $pres->id }}" class="text-[12px] text-gray-600">Activo</label>
                        </div>
                        <button type="submit"
                            class="h-10 px-4 bg-[#003594] text-white rounded-lg text-[13px] font-semibold hover:bg-[#002470] transition">
                            Actualizar
                        </button>
                    </form>
                </div>

                {{-- Presentation prices table --}}
                @if($pres->sedePrices->isNotEmpty())
                <table class="w-full text-[12px] mb-3">
                    <thead>
                        <tr class="text-gray-400">
                            <th class="text-left py-1 pr-4 font-semibold">Sede</th>
                            <th class="text-right py-1 pr-4 font-semibold">Precio</th>
                            <th class="text-right py-1 font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($pres->sedePrices as $pp)
                        <tr>
                            <td class="py-1 pr-4 text-gray-700">
                                @if($pp->sede)
                                    {{ $pp->sede->nombre }}
                                @else
                                    <span class="italic text-gray-400">Global (todas las sedes)</span>
                                @endif
                            </td>
                            <td class="py-1 pr-4 text-right font-semibold tabular-nums">${{ number_format($pp->precio_venta, 2) }}</td>
                            <td class="py-1 text-right">
                                <form method="POST" action="{{ route('presentation-prices.destroy', [$product, $pres, $pp]) }}"
                                      onsubmit="return confirm('¿Eliminar este precio?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600 transition">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-[12px] text-gray-400 italic mb-3">Sin precios configurados — esta presentación no puede venderse en ninguna sede.</p>
                @endif

                {{-- Add price for this presentation --}}
                <div class="bg-gray-50/60 rounded-lg p-3 border border-gray-100">
                    <p class="text-[11px] font-semibold text-gray-500 mb-2">Agregar precio para esta presentación</p>
                    <form method="POST" action="{{ route('presentation-prices.store', [$product, $pres]) }}"
                          class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Sede (vacío = global)</label>
                            <select name="sede_id"
                                class="border border-gray-200 rounded-lg px-3 py-2 text-[12px] text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                                <option value="">— Global —</option>
                                @foreach($sedes as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Precio</label>
                            <input type="number" name="precio_venta" step="0.01" min="0" required placeholder="0.00"
                                class="border border-gray-200 rounded-lg px-3 py-2 text-[12px] w-28 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                        </div>
                        <button type="submit"
                            class="h-9 px-3 bg-[#003594] text-white rounded-lg text-[12px] font-semibold hover:bg-[#002470] transition">
                            Guardar
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <p class="px-6 py-4 text-[13px] text-gray-400 italic">Sin presentaciones. Agrega una abajo.</p>
        @endforelse

        {{-- Add new presentation form --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
            <p class="text-[12px] font-semibold text-gray-600 mb-3">Agregar presentación</p>
            <form method="POST" action="{{ route('product-presentations.store', $product) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Nombre</label>
                    <input type="text" name="nombre" required maxlength="100" placeholder="Caja x 24"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-[13px] w-40 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Factor (unidades base)</label>
                    <input type="number" name="factor_stock" min="1" required placeholder="24"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-[13px] w-24 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Orden</label>
                    <input type="number" name="sort_order" min="0" value="0" placeholder="0"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-[13px] w-20 bg-white focus:outline-none focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594]">
                </div>
                <button type="submit"
                    class="h-10 px-4 bg-[#003594] text-white rounded-lg text-[13px] font-semibold hover:bg-[#002470] transition">
                    Crear
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
