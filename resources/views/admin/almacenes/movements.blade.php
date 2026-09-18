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

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Tipo</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Cantidad</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Motivo</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Usuario</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Referencia</th>
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
                    $typeColor = $typeColors[$mov->tipo] ?? 'bg-gray-100 text-gray-600 border-gray-200';
                    $typeLabel = $typeLabels[$mov->tipo] ?? ucfirst($mov->tipo);
                    $isPositive = in_array($mov->tipo, ['entrada']) || ($mov->tipo === 'ajuste' && $mov->cantidad > 0);
                    $isNegative = in_array($mov->tipo, ['salida', 'pérdida', 'transferencia']) || ($mov->tipo === 'ajuste' && $mov->cantidad < 0);
                @endphp
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap text-[12px]">{{ $mov->fecha_movimiento->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800">{{ $mov->product?->nombre ?? '—' }}</p>
                        @if($mov->product?->sku)
                            <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ $mov->product->sku }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $typeColor }}">
                            {{ $typeLabel }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center font-semibold {{ $isPositive ? 'text-emerald-600' : ($isNegative ? 'text-red-600' : 'text-gray-700') }}">
                        {{ $isPositive ? '+' : ($isNegative ? '−' : '') }}{{ abs($mov->cantidad) }}
                    </td>
                    <td class="px-5 py-3 text-gray-600 max-w-[200px] truncate">{{ $mov->motivo }}</td>
                    <td class="px-5 py-3 text-[12px] text-gray-500">{{ $mov->user?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-[12px] text-gray-400">
                        @if($mov->reference_type === 'transfer' && $mov->reference_id)
                            <a href="{{ route('transfers.show', $mov->reference_id) }}" class="text-stock-primary hover:underline">
                                #Transf. {{ $mov->reference_id }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-[13px] text-gray-400">Sin movimientos registrados para este almacén</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $movements->links() }}
        </div>
    </div>

</div>
@endsection
