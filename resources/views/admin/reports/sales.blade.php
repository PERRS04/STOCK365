@extends('layouts.app')

@section('title', 'Reporte de Ventas')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="page-title">Reporte de Ventas</h1>
        <a href="{{ route('reports.export-sales') }}" class="btn btn-primary btn-sm">
            Exportar Excel
        </a>
    </div>

    <livewire:reports.sales-report />
</div>
@endsection
