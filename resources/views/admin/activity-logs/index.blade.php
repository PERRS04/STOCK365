@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Auditoría del Sistema</h1>
            <p class="page-subtitle mt-1">Registro completo de todas las acciones críticas</p>
        </div>
        @can('reports.export')
        <a href="{{ route('activity-logs.export') }}" class="btn btn-primary btn-sm gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar Excel
        </a>
        @endcan
    </div>

    <livewire:admin.activity-log-table />
</div>
@endsection
