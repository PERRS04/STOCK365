@extends('layouts.app')
@section('title', $almacen->nombre . ' — Movimientos')
@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('almacenes.index') }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">← Almacenes</a>
            <span class="text-gray-200">/</span>
            <a href="{{ route('almacenes.show', $almacen) }}" class="text-[12px] text-gray-400 hover:text-gray-600 transition">{{ $almacen->nombre }}</a>
            <span class="text-gray-200">/</span>
            <h1 class="text-[18px] font-semibold text-gray-900">Movimientos</h1>
        </div>
        <span class="text-[12px] text-gray-400">{{ $movements->total() }} registros</span>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('almacenes.movements', $almacen) }}"
          class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] px-5 py-4">
        <div class="flex flex-wrap gap-3 items-end">

            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}"
                       class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
            </div>

            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}"
                       class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
            </div>

            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Producto</label>
                <select name="product_id"
                        class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                    <option value="">Todos los productos</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Tipo</label>
                <select name="tipo"
                        class="px-3 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                    <option value="">Todos los tipos</option>
                    @foreach(['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste', 'pérdida' => 'Pérdida', 'transferencia' => 'Transferencia'] as $val => $label)
                        <option value="{{ $val }}" {{ request('tipo') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="px-4 py-1.5 text-[12px] font-semibold bg-stock-primary text-white rounded-lg hover:bg-stock-primary/90 transition">
                Filtrar
            </button>

            @if(request()->hasAny(['desde','hasta','product_id','tipo']))
            <a href="{{ route('almacenes.movements', $almacen) }}"
               class="px-4 py-1.5 text-[12px] font-medium text-gray-500 border border-gray-200 rounded-lg hover:border-gray-300 transition">
                Limpiar
            </a>
            @endif
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100 bg-gray-50/50">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Tipo</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Entrada</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Salida</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Costo unit.</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Usuario</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Motivo / Referencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($movements as $mov)
                @php
                    $typeColors = [
                        'entrada'       => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'salida'        => 'bg-gray-100 text-gray-600 border-gray-200',
                        'ajuste'        => 'bg-blue-50 text-blue-700 border-blue-200',
                        'pérdida'       => 'bg-red-50 text-red-700 border-red-200',
                        'transferencia' => 'bg-amber-50 text-amber-700 border-amber-200',
                    ];
                    $typeLabels = [
                        'entrada'       => 'Entrada',
                        'salida'        => 'Salida',
                        'ajuste'        => 'Ajuste',
                        'pérdida'       => 'Pérdida',
                        'transferencia' => 'Transferencia',
                    ];
                    $tc     = $typeColors[$mov->tipo] ?? 'bg-gray-100 text-gray-600 border-gray-200';
                    $tl     = $typeLabels[$mov->tipo] ?? ucfirst($mov->tipo);
                    $isEntry = $mov->tipo === 'entrada' || ($mov->tipo === 'ajuste' && $mov->cantidad > 0);
                    $isExit  = in_array($mov->tipo, ['salida', 'pérdida', 'transferencia']) || ($mov->tipo === 'ajuste' && $mov->cantidad < 0);
                @endphp
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-2.5 text-gray-500 whitespace-nowrap text-[12px]">{{ $mov->fecha_movimiento->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-2.5">
                        <p class="font-medium text-gray-800">{{ $mov->product?->nombre ?? '—' }}</p>
                        @if($mov->product?->sku)
                            <p class="text-[11px] text-gray-400 font-mono">{{ $mov->product->sku }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-2.5 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $tc }}">{{ $tl }}</span>
                    </td>
                    <td class="px-5 py-2.5 text-right font-semibold {{ $isEntry ? 'text-emerald-600' : 'text-gray-300' }}">
                        {{ $isEntry ? '+' . abs($mov->cantidad) : '—' }}
                    </td>
                    <td class="px-5 py-2.5 text-right font-semibold {{ $isExit ? 'text-red-600' : 'text-gray-300' }}">
                        {{ $isExit ? '−' . abs($mov->cantidad) : '—' }}
                    </td>
                    <td class="px-5 py-2.5 text-right text-[12px] text-gray-500">
                        {{ $mov->costo_unitario ? '$' . number_format($mov->costo_unitario, 2) : '—' }}
                    </td>
                    <td class="px-5 py-2.5 text-[12px] text-gray-500 whitespace-nowrap">{{ $mov->user?->name ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-[12px] text-gray-500 max-w-[220px]">
                        @if($mov->motivo)
                            <p class="truncate">{{ $mov->motivo }}</p>
                        @endif
                        @if($mov->reference_type === 'receipt' && $mov->reference_id)
                            <span class="text-[11px] text-gray-400">Recepción #{{ $mov->reference_id }}</span>
                        @elseif($mov->reference_type === 'transfer' && $mov->reference_id)
                            <a href="{{ route('transfers.show', $mov->reference_id) }}" class="text-[11px] text-stock-primary hover:underline">
                                Transf. #{{ $mov->reference_id }}
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-[13px] text-gray-400">Sin movimientos para los filtros seleccionados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $movements->links() }}
        </div>
    </div>

</div>
@endsection
