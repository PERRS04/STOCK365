@extends('layouts.app')
@section('title', 'Kardex')
@section('content')

<div class="space-y-5">

    <h1 class="page-title">Kardex de Inventario</h1>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('kardex.index') }}"
          class="bg-white rounded-2xl border border-gray-200/60 shadow-card px-5 py-4 flex flex-wrap gap-3 items-end">

        <div class="flex-1 min-w-[200px]">
            <label class="form-label">Producto *</label>
            <select name="product_id" required
                    class="input input-sm w-full">
                <option value="">— Seleccionar producto —</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->nombre }}{{ $p->marca ? ' · '.$p->marca : '' }}{{ $p->tamaño ? ' · '.$p->tamaño : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Sede</label>
            <select name="sede_id"
                    class="input input-sm">
                <option value="">Todas</option>
                @foreach($sedes as $s)
                    <option value="{{ $s->id }}" {{ request('sede_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}"
                   class="input input-sm">
        </div>

        <div>
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}"
                   class="input input-sm">
        </div>

        <button type="submit" class="btn btn-primary btn-sm">
            Consultar
        </button>
    </form>

    @if($product)

    {{-- Product header --}}
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card px-5 py-4 flex items-center justify-between">
        <div>
            <p class="text-[11px] text-gray-400 uppercase tracking-widest">Producto consultado</p>
            <p class="text-[16px] font-semibold text-gray-900 mt-0.5">{{ $product->nombre }}
                @if($product->marca) <span class="text-gray-500 font-normal">· {{ $product->marca }}</span> @endif
                @if($product->tamaño) <span class="text-gray-500 font-normal">· {{ $product->tamaño }}</span> @endif
            </p>
        </div>
        <div class="flex gap-6 text-right">
            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest">Saldo inicial</p>
                <p class="text-[16px] font-bold text-gray-900">{{ $saldoInicial }}</p>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest">Movimientos</p>
                <p class="text-[16px] font-bold text-gray-900">{{ $movements->count() }}</p>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest">Saldo final</p>
                <p class="text-[16px] font-bold text-stock-primary">{{ $movements->last()?->saldo_acumulado ?? $saldoInicial }}</p>
            </div>
        </div>
    </div>

    {{-- Movements table --}}
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100 bg-gray-50/50">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Tipo</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Motivo</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Entrada</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Salida</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Saldo</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Costo Unit.</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($movements as $mov)
                @php
                    $esEntrada = in_array($mov->tipo, ['entrada', 'ajuste']);
                    $tipoMap = [
                        'entrada'       => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Entrada'],
                        'salida'        => ['bg-red-50 text-red-700 border-red-200', 'Salida'],
                        'ajuste'        => ['bg-blue-50 text-blue-700 border-blue-200', 'Ajuste'],
                        'pérdida'       => ['bg-orange-50 text-orange-700 border-orange-200', 'Pérdida'],
                        'transferencia' => ['bg-purple-50 text-purple-700 border-purple-200', 'Transf.'],
                    ];
                    [$tipoCls, $tipoLabel] = $tipoMap[$mov->tipo] ?? ['bg-gray-100 text-gray-600 border-gray-200', $mov->tipo];
                @endphp
                <tr class="hover:bg-gray-50/40">
                    <td class="px-5 py-2.5 text-gray-500 whitespace-nowrap text-[12px]">{{ $mov->fecha_movimiento->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ $tipoCls }}">{{ $tipoLabel }}</span>
                    </td>
                    <td class="px-5 py-2.5 text-gray-700 max-w-[200px] truncate">{{ $mov->motivo }}</td>
                    <td class="px-5 py-2.5 text-gray-500 text-[12px]">{{ $mov->sede?->nombre ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-right font-medium {{ $esEntrada ? 'text-emerald-700' : 'text-gray-300' }}">
                        {{ $esEntrada ? $mov->cantidad : '—' }}
                    </td>
                    <td class="px-5 py-2.5 text-right font-medium {{ !$esEntrada ? 'text-red-600' : 'text-gray-300' }}">
                        {{ !$esEntrada ? $mov->cantidad : '—' }}
                    </td>
                    <td class="px-5 py-2.5 text-right font-bold text-gray-900">{{ $mov->saldo_acumulado }}</td>
                    <td class="px-5 py-2.5 text-right text-gray-600 text-[12px]">
                        {{ $mov->costo_unitario ? '$'.number_format($mov->costo_unitario, 2) : '—' }}
                    </td>
                    <td class="px-5 py-2.5 text-gray-500 text-[12px]">{{ $mov->user->name ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-8 text-center text-[13px] text-gray-400">Sin movimientos en el período seleccionado</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @else
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card px-5 py-12 text-center">
        <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-[14px] font-medium text-gray-500">Selecciona un producto para ver su kardex</p>
        <p class="text-[12px] text-gray-400 mt-1">Historial completo de entradas, salidas y saldo</p>
    </div>
    @endif

</div>
@endsection
