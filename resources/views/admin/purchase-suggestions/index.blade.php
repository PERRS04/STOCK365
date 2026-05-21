@extends('layouts.app')
@section('title', 'Sugerencias de Compra')
@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-[18px] font-semibold text-gray-900">Sugerencias de Compra</h1>
        <div class="flex gap-3 text-[13px]">
            @if($criticalCount > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-red-700 font-medium text-[12px]">
                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                {{ $criticalCount }} crítico{{ $criticalCount !== 1 ? 's' : '' }}
            </span>
            @endif
            @if($alertCount > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 font-medium text-[12px]">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                {{ $alertCount }} alerta{{ $alertCount !== 1 ? 's' : '' }}
            </span>
            @endif
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex items-end gap-3">
        <div>
            <label class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 mb-1.5">Sede</label>
            <select name="sede_id" class="px-3 py-2 text-[13px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
                <option value="">Todas las sedes</option>
                @foreach($sedes as $s)
                    <option value="{{ $s->id }}" {{ $sedeId == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-stock-primary text-white text-[13px] font-medium rounded-lg hover:bg-stock-primary/90 transition">Filtrar</button>
    </form>

    @if($suggestions->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] px-5 py-12 text-center">
        <svg class="w-10 h-10 text-emerald-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-[14px] font-medium text-gray-700">Stock en buen nivel</p>
        <p class="text-[12px] text-gray-400 mt-1">No hay productos que requieran reabastecimiento inmediato</p>
    </div>
    @else

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100 bg-gray-50/50">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Producto</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Stock Actual</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Mínimo</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Recomendado</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Consumo/día</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Días restantes</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sugerido</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Urgencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($suggestions as $s)
                @php
                    $urgMap = [
                        'critico' => ['bg-red-50/80', 'bg-red-50 text-red-700 border-red-200', 'Crítico'],
                        'alerta'  => ['bg-amber-50/50', 'bg-amber-50 text-amber-700 border-amber-200', 'Alerta'],
                        'normal'  => ['', 'bg-gray-100 text-gray-600 border-gray-200', 'Normal'],
                    ];
                    [$rowCls, $badgeCls, $urgLabel] = $urgMap[$s['urgencia']];
                @endphp
                <tr class="hover:bg-gray-50/50 {{ $rowCls }}">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-900">{{ $s['product']->nombre }}</p>
                        <p class="text-[11px] text-gray-400">{{ $s['product']->marca }} {{ $s['product']->tamaño }}</p>
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $s['sede']->nombre }}</td>
                    <td class="px-5 py-3 text-right font-bold {{ $s['stock_actual'] <= $s['stock_minimo'] ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $s['stock_actual'] }}
                    </td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ $s['stock_minimo'] }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ $s['stock_recomendado'] }}</td>
                    <td class="px-5 py-3 text-right text-gray-600">{{ $s['avg_diario'] }}</td>
                    <td class="px-5 py-3 text-right {{ is_numeric($s['dias_restantes']) && $s['dias_restantes'] < 5 ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                        @if($s['dias_restantes'] !== null)
                            ~{{ $s['dias_restantes'] }} días
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right font-bold text-stock-primary">{{ $s['suggested_qty'] }} uds</td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ $badgeCls }}">{{ $urgLabel }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @endif

</div>
@endsection
