@extends('layouts.app')

@section('title', 'Inteligencia de Equipo')

@section('content')
<div class="min-h-screen bg-[#f4f6fb]">

    {{-- ── Page Header ──────────────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-gray-100 px-6 py-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-[20px] font-black text-[#001240] tracking-tight leading-none">Inteligencia de Equipo</h1>
                <p class="text-[12px] text-gray-400 mt-1 font-medium">Rendimiento operacional · Disciplina · Evolución · Talento</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#003594]/60 px-3 py-1.5 rounded-full bg-[#003594]/8 border border-[#003594]/12">
                    Solo Tú ves esto
                </span>
            </div>
        </div>
    </div>

    {{-- ── Livewire Dashboard ───────────────────────────────────────────────── --}}
    @livewire('workforce-dashboard')

</div>
@endsection
