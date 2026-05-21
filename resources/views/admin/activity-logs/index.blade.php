@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Auditoría del Sistema</h1>
            <p class="text-sm text-gray-500 mt-1">Registro completo de todas las acciones críticas</p>
        </div>
        @can('reports.export')
        <a href="{{ route('activity-logs.export') }}"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium text-sm transition flex items-center gap-2">
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
