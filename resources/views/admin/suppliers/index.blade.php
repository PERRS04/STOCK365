@extends('layouts.app')
@section('title', 'Proveedores')
@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-[18px] font-semibold text-gray-900">Proveedores</h1>
        @can('products.create')
        <a href="{{ route('suppliers.create') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-stock-primary text-white text-[13px] font-medium hover:bg-stock-primary/90 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Proveedor
        </a>
        @endcan
    </div>

    {{-- Stats strip --}}
    <div class="flex gap-3">
        <a href="{{ route('suppliers.index') }}"
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition
                  {{ !request('estado') || request('estado') === 'activo' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Activos <span class="font-bold ml-1">{{ $totalActive }}</span>
        </a>
        <a href="{{ route('suppliers.index', ['estado' => 'inactivo']) }}"
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-[12px] font-medium transition
                  {{ request('estado') === 'inactivo' ? 'bg-gray-100 border-gray-300 text-gray-800' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
            Inactivos <span class="font-bold ml-1">{{ $totalInactive }}</span>
        </a>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('suppliers.index') }}" class="flex gap-2">
        @if(request('estado'))
            <input type="hidden" name="estado" value="{{ request('estado') }}">
        @endif
        <input type="text" name="q" value="{{ request('q') }}"
               placeholder="Buscar por nombre, RUC, email…"
               class="flex-1 px-3 py-2 text-[13px] rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary">
        <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-[13px] font-medium rounded-lg transition">
            Buscar
        </button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden">
        <table class="w-full text-[13px]">
            <thead class="border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Proveedor</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Contacto</th>
                    <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">RUC / NIT</th>
                    <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Total Comprado</th>
                    <th class="text-center px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Compras</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-900">{{ $supplier->nombre }}</p>
                        @if($supplier->email)
                            <p class="text-[11px] text-gray-400">{{ $supplier->email }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        <p>{{ $supplier->contacto_principal ?? '—' }}</p>
                        @if($supplier->telefono)
                            <p class="text-[11px] text-gray-400">{{ $supplier->telefono }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500 font-mono text-[12px]">{{ $supplier->ruc_nit ?? '—' }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-gray-900">
                        ${{ number_format($supplier->total_comprado ?? 0, 2) }}
                    </td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ $supplier->compras_count ?? 0 }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="text-[12px] font-medium text-stock-primary hover:underline">Ver →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-[13px] text-gray-400">
                        Sin proveedores registrados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3.5 border-t border-gray-100">
            {{ $suppliers->links() }}
        </div>
    </div>

</div>
@endsection
