@extends('layouts.app')
@section('title', 'Pagos Pendientes')
@section('content')

<div class="max-w-3xl space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-[20px] font-bold text-gray-900 leading-none tracking-tight">Pagos Pendientes</h1>
            <p class="text-[12px] text-gray-400 mt-1">Aportes asignados a {{ auth()->user()->sede?->nombre }} para confirmar</p>
        </div>
        @if($allocations->count() > 0)
        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/80 px-3 py-1.5 rounded-lg">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
            {{ $allocations->count() }} pendiente{{ $allocations->count() !== 1 ? 's' : '' }}
        </span>
        @endif
    </div>

    @if(session('success'))
    <div class="flex items-center gap-2.5 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[13px] text-emerald-700 font-medium">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Proveedor</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Sede origen</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Fecha</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Monto</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($allocations as $alloc)
                <tr class="hover:bg-gray-50/50 transition-colors">

                    <td class="px-5 py-3.5">
                        <p class="font-semibold text-gray-800">{{ $alloc->receipt->supplier_name }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Recepción #{{ $alloc->receipt->id }}</p>
                    </td>

                    <td class="px-5 py-3.5 text-gray-600">
                        {{ $alloc->receipt->sede?->nombre ?? '—' }}
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $alloc->receipt->user?->name ?? '—' }}</p>
                    </td>

                    <td class="px-5 py-3.5 text-[12px] text-gray-500 whitespace-nowrap">
                        {{ $alloc->created_at->format('d/m/Y H:i') }}
                    </td>

                    <td class="px-5 py-3.5 text-right">
                        <span class="text-[15px] font-bold text-amber-700 num">
                            {{ formatCurrency($alloc->amount) }}
                        </span>
                    </td>

                    <td class="px-5 py-3.5 text-right">
                        <a href="{{ route('sede-payments.confirm.form', $alloc) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-semibold bg-stock-primary hover:bg-blue-800 text-white rounded-lg transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Confirmar pago
                        </a>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-14 text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-[13px] font-semibold text-gray-700">Sin pagos pendientes</p>
                        <p class="text-[12px] text-gray-400 mt-0.5">No hay aportes asignados a esta sede</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
