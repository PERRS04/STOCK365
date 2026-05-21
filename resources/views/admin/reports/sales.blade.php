@extends('layouts.app')

@section('title', 'Reporte de Ventas')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Reporte de Ventas</h1>
        <a href="{{ route('reports.export-sales') }}"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium text-sm transition">
            Exportar Excel
        </a>
    </div>

    <livewire:reports.sales-report />
</div>
@endsection
