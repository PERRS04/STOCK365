@extends('layouts.app')
@section('title', 'Cierres Pendientes')
@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-[20px] font-bold text-gray-900 leading-none tracking-tight">Cierres Pendientes</h1>
            <p class="text-[12px] text-gray-400 mt-1">{{ $closings->total() }} pendiente{{ $closings->total() !== 1 ? 's' : '' }} de aprobación</p>
        </div>
        @if($closings->total() > 0)
        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/80 px-3 py-1.5 rounded-lg">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
            Requiere revisión
        </span>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Operador</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Caja esperada</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Efectivo</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Diferencia</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">→ Apertura</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($closings as $closing)
                @php
                    $diff     = $closing->diferencia;
                    $balanced = abs($diff) < 0.01;
                    $surplus  = $diff > 0.01;
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors">

                    <td class="px-5 py-3.5 text-[12px] text-gray-500 whitespace-nowrap">
                        {{ $closing->fecha_cierre->format('d/m/Y H:i') }}
                    </td>

                    <td class="px-5 py-3.5 font-semibold text-gray-800">
                        {{ $closing->sede?->nombre ?? '—' }}
                    </td>

                    <td class="px-5 py-3.5 text-gray-600">
                        {{ $closing->user?->name ?? '—' }}
                    </td>

                    <td class="px-5 py-3.5 text-right font-semibold text-gray-800 num">
                        {{ formatCurrency($closing->total_sistema) }}
                    </td>

                    <td class="px-5 py-3.5 text-right text-gray-600 num">
                        {{ formatCurrency($closing->efectivo) }}
                    </td>

                    <td class="px-5 py-3.5 text-right">
                        <span class="text-[13px] font-bold num
                            {{ $balanced ? 'text-emerald-600' : ($surplus ? 'text-blue-600' : 'text-red-600') }}">
                            {{ $diff >= 0 ? '+' : '' }}{{ formatCurrency($diff) }}
                        </span>
                        @if(!$balanced)
                        <p class="text-[10px] mt-0.5 {{ $surplus ? 'text-blue-400' : 'text-red-400' }}">
                            {{ $surplus ? 'Sobrante' : 'Faltante' }}
                        </p>
                        @endif
                    </td>

                    <td class="px-5 py-3.5 text-right">
                        <span class="text-[12px] font-semibold text-gray-700 num">
                            ${{ number_format($closing->efectivo, 2) }}
                        </span>
                        <p class="text-[10px] text-gray-400 mt-0.5">próxima apertura</p>
                    </td>

                    <td class="px-5 py-3.5">
                        <div class="flex items-center justify-end gap-2">
                            <form action="{{ route('cash-closings.approve', $closing) }}" method="POST"
                                  data-confirm="¿Aprobar el cierre de {{ $closing->user?->name ?? 'operador' }}?">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="px-3 py-1.5 text-[11px] font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                                    Aprobar
                                </button>
                            </form>
                            <form action="{{ route('cash-closings.reject', $closing) }}" method="POST"
                                  data-confirm="¿Rechazar el cierre de {{ $closing->user?->name ?? 'operador' }}?">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="px-3 py-1.5 text-[11px] font-semibold border border-red-200 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    Rechazar
                                </button>
                            </form>
                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-14 text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-[13px] font-semibold text-gray-700">Sin cierres pendientes</p>
                        <p class="text-[12px] text-gray-400 mt-0.5">Todos los cierres han sido procesados</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($closings->hasPages())
        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $closings->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
