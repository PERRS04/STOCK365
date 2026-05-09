@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    @if(auth()->user()->isBoss())
        <!-- BOSS DASHBOARD -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Ventas Hoy -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-stock-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Venta Total (Hoy)</p>
                        <p class="text-3xl font-bold text-stock-primary mt-2">S/ {{ number_format($totalSalesToday, 2) }}</p>
                    </div>
                    <svg class="w-12 h-12 text-stock-accent opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <!-- Transacciones -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Transacciones (Hoy)</p>
                        <p class="text-3xl font-bold text-blue-500 mt-2">{{ $transactionCount }}</p>
                    </div>
                    <svg class="w-12 h-12 text-blue-500 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
            </div>

            <!-- Cierres Pendientes -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Cierres Pendientes</p>
                        <p class="text-3xl font-bold text-yellow-500 mt-2">{{ $pendingClosings }}</p>
                        @if($pendingClosings > 0)
                            <a href="{{ route('cash-closings.pending') }}" class="text-sm text-yellow-600 hover:text-yellow-800 mt-2 inline-block">Ver →</a>
                        @endif
                    </div>
                    <svg class="w-12 h-12 text-yellow-500 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <!-- Alertas Stock -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Alertas Stock</p>
                        <p class="text-3xl font-bold text-red-500 mt-2">{{ $alertCount }}</p>
                        @if($alertCount > 0)
                            <p class="text-xs text-red-600 mt-2">Productos bajos</p>
                        @endif
                    </div>
                    <svg class="w-12 h-12 text-red-500 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M7 9h10M7 13h10M7 17h10"></path></svg>
                </div>
            </div>
        </div>

        <!-- Gráficos y Tablas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Productos -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Productos Más Vendidos</h3>
                <div class="space-y-3">
                    @forelse($topProducts as $product)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div>
                                <p class="font-medium text-gray-800">{{ $product->nombre }}</p>
                                <p class="text-sm text-gray-600">{{ $product->marca }} - {{ $product->tamaño }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-stock-primary">{{ $product->saleItems->sum('cantidad') }} unid.</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">Sin datos disponibles</p>
                    @endforelse
                </div>
            </div>

            <!-- Cierres Pendientes -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">⏳ Cierres Pendientes de Aprobación</h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @forelse($pendingCashClosings as $closing)
                        <div class="p-3 border border-yellow-200 bg-yellow-50 rounded">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $closing->sede->nombre }}</p>
                                    <p class="text-sm text-gray-600">{{ $closing->user->name }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $closing->fecha_cierre->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold">S/ {{ number_format($closing->total_sistema, 2) }}</p>
                                    @if($closing->isBalanced())
                                        <p class="text-xs text-green-600">✅ Equilibrado</p>
                                    @else
                                        <p class="text-xs text-red-600">Diferencia: S/ {{ number_format($closing->diferencia, 2) }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No hay cierres pendientes</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Alertas de Stock -->
        @if($stockAlerts->count() > 0)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🚨 Alertas de Stock Bajo</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-3 font-semibold text-gray-700">Producto</th>
                                <th class="text-left py-2 px-3 font-semibold text-gray-700">Sede</th>
                                <th class="text-center py-2 px-3 font-semibold text-gray-700">Stock Actual</th>
                                <th class="text-center py-2 px-3 font-semibold text-gray-700">Stock Mínimo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockAlerts as $alert)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-2 px-3"><strong>{{ $alert->product->nombre }}</strong></td>
                                    <td class="py-2 px-3">{{ $alert->sede->nombre }}</td>
                                    <td class="py-2 px-3 text-center text-red-600 font-bold">{{ $alert->stock_actual }}</td>
                                    <td class="py-2 px-3 text-center">{{ $alert->stock_minimo }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <!-- OPERADOR DASHBOARD -->
        <div class="bg-gradient-to-r from-stock-primary to-blue-700 rounded-lg shadow-lg p-8 text-white">
            <h1 class="text-4xl font-bold mb-2">¡Bienvenido!</h1>
            <p class="text-stock-accent text-lg">{{ $sede->nombre }}</p>
            <p class="text-gray-200 mt-1">{{ now()->format('l, d \\d\\e F \\d\\e Y') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Botón Nueva Venta -->
            <a href="{{ route('pos.create') }}" class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition transform hover:scale-105 cursor-pointer border-2 border-stock-accent">
                <div class="text-center">
                    <svg class="w-16 h-16 text-stock-accent mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <h2 class="text-2xl font-bold text-stock-primary mb-2">💰 NUEVA VENTA</h2>
                    <p class="text-gray-600">Registra una nueva venta de productos</p>
                </div>
            </a>

            <!-- Botón Cierre de Caja -->
            <a href="{{ route('cash-closing.create') }}" class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition transform hover:scale-105 cursor-pointer border-2 border-green-500">
                <div class="text-center">
                    <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h2 class="text-2xl font-bold text-green-600 mb-2">💵 CIERRE DE CAJA</h2>
                    <p class="text-gray-600">Cierre el dinero del día</p>
                </div>
            </a>
        </div>

        <!-- Stock en Alerta -->
        @if($lowStockProducts->count() > 0)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="text-red-500 mr-2">⚠️</span> Productos con Stock Bajo
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($lowStockProducts as $item)
                        <div class="p-4 bg-red-50 border border-red-200 rounded">
                            <p class="font-bold text-gray-800">{{ $item->product->nombre }}</p>
                            <p class="text-sm text-gray-600">{{ $item->product->marca }} - {{ $item->product->tamaño }}</p>
                            <p class="text-sm mt-2">
                                <span class="text-red-600 font-bold">Stock: {{ $item->cantidad_stock }}</span>
                                <span class="text-gray-600"> (Mín: {{ $item->product->stock_minimo }})</span>
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Últimas Ventas del Día -->
        @if($todaysSales->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 Ventas de Hoy</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-3 font-semibold text-gray-700">Hora</th>
                                <th class="text-left py-2 px-3 font-semibold text-gray-700">Items</th>
                                <th class="text-right py-2 px-3 font-semibold text-gray-700">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todaysSales as $sale)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-2 px-3">{{ $sale->fecha_venta->format('H:i') }}</td>
                                    <td class="py-2 px-3">{{ $sale->items->count() }} productos</td>
                                    <td class="py-2 px-3 text-right font-bold text-stock-primary">S/ {{ number_format($sale->total_sistema, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Último Cierre -->
        @if($lastClosing)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 {{ $lastClosing->estado === 'aprobado' ? 'border-green-500' : 'border-yellow-500' }}">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Último Cierre de Caja</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-gray-600 text-sm">Fecha</p>
                        <p class="font-bold">{{ $lastClosing->fecha_cierre->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Total Sistema</p>
                        <p class="font-bold text-stock-primary">S/ {{ number_format($lastClosing->total_sistema, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Diferencia</p>
                        <p class="font-bold {{ $lastClosing->isBalanced() ? 'text-green-600' : 'text-red-600' }}">S/ {{ number_format($lastClosing->diferencia, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Estado</p>
                        <p class="font-bold {{ $lastClosing->estado === 'aprobado' ? 'text-green-600' : 'text-yellow-600' }}">
                            @if($lastClosing->estado === 'aprobado')
                                ✅ Aprobado
                            @elseif($lastClosing->estado === 'rechazado')
                                ❌ Rechazado
                            @else
                                ⏳ Pendiente
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
