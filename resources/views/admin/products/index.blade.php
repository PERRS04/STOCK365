@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Productos</h1>
        <a href="{{ route('products.create') }}"
            class="bg-stock-primary text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition font-medium">
            + Nuevo Producto
        </a>
    </div>

    <livewire:products.products-table />
</div>
@endsection
