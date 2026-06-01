@extends('layouts.app')
@section('title', 'Estado de Caja')

@section('content')
<div class="space-y-5">

{{-- ══════════════════════════════════════════════════════
     STATUS: NO SESSION
══════════════════════════════════════════════════════ --}}
@if(!$session)

<div class="rounded-3xl overflow-hidden"
     style="background: linear-gradient(135deg, #060e1f 0%, #0b1a3d 50%, #003594 100%);">

    {{-- Idle accent bar --}}
    <div class="h-[3px] bg-gradient-to-r from-gray-600 via-gray-500 to-gray-600"></div>

    <div class="flex items-center justify-between px-10 py-10 flex-wrap gap-6">
        <div class="flex items-center gap-6">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0"
                 style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10)">
                <svg class="w-7 h-7" fill="none" stroke="rgba(255,255,255,0.30)" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <p style="color:rgba(255,255,255,0.30);font-size:9px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;margin-bottom:8px">
                    {{ $sede->nombre }} · Caja
                </p>
                <p style="color:#fff;font-size:22px;font-weight:800;line-height:1.2;letter-spacing:-.01em">
                    Sin sesión activa
                </p>
                <p style="color:rgba(255,255,255,0.35);font-size:13px;margin-top:8px">
                    Abre tu caja para comenzar a registrar ventas y movimientos
                </p>
            </div>
        </div>
        <a href="{{ route('cash-session.create') }}"
           style="background:#FFD100;color:#003594;padding:14px 28px;border-radius:14px;font-weight:800;font-size:14px;letter-spacing:.02em;display:inline-flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0;transition:opacity .15s"
           onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Abrir caja
        </a>
    </div>
</div>

@else

{{-- ══════════════════════════════════════════════════════
     LIVE CASH ENGINE HERO
══════════════════════════════════════════════════════ --}}

{{-- Session header strip --}}
<div class="flex items-center justify-between flex-wrap gap-3">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            @if($session->isOpen())
            <span class="relative flex h-2 w-2 shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-emerald-600">Caja activa</span>
            @else
            <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
            <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-amber-600">Cierre pendiente</span>
            @endif
        </div>
        <h1 class="text-[22px] font-bold text-gray-900 leading-none tracking-tight">{{ $sede->nombre }}</h1>
        <p class="text-[12px] text-gray-400 mt-1">
            Apertura {{ $session->opened_at->format('H:i') }}
            &nbsp;·&nbsp; {{ $session->duration }}h activa
            @if($session->sales_count > 0)
            &nbsp;·&nbsp;
            <span class="text-emerald-600 font-semibold">{{ $session->sales_count }} venta{{ $session->sales_count !== 1 ? 's' : '' }}</span>
            @endif
        </p>
    </div>

    {{-- Quick action buttons --}}
    @if($session->isOpen())
    <div class="flex items-center gap-3 flex-wrap">
        <a href="{{ route('pos.create') }}"
           class="inline-flex items-center gap-2.5 bg-[#003594] hover:bg-[#002470] text-white font-bold text-[13px]
                  px-5 py-2.5 rounded-xl transition-all duration-150 shadow-[0_4px_14px_rgba(0,53,148,0.25)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            Ir a ventas
        </a>
        <a href="{{ route('cash-closing.create') }}"
           class="inline-flex items-center gap-2.5 border border-gray-200 hover:border-gray-300 hover:bg-gray-50
                  text-gray-700 font-semibold text-[13px] px-5 py-2.5 rounded-xl transition-all duration-150">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Cerrar caja
        </a>
        <a href="{{ route('cash-movements.create') }}"
           class="inline-flex items-center gap-2 border border-gray-200 hover:border-gray-300 hover:bg-gray-50
                  text-gray-500 font-medium text-[12px] px-4 py-2.5 rounded-xl transition-all duration-150">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Movimiento
        </a>
    </div>
    @endif
</div>

{{-- Live Cash Engine widget (full width) --}}
@livewire('live-cash-box')

{{-- ── Session detail grid ─────────────────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    {{-- Opening detail --}}
    <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card p-5">
        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-4">Detalle de apertura</p>
        <dl class="space-y-3">
            <div class="flex justify-between items-center">
                <dt class="text-[12px] text-gray-500">Fondo inicial</dt>
                <dd class="text-[13px] font-bold text-gray-900 tabular-nums">{{ formatCurrency($session->opening_amount) }}</dd>
            </div>
            @if($session->inheritedFromClosing)
            <div class="flex justify-between items-center">
                <dt class="text-[12px] text-gray-500">Origen</dt>
                <dd class="flex items-center gap-1.5">
                    <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Heredada</span>
                    <span class="text-[11px] text-gray-400">{{ $session->inheritedFromClosing->fecha_cierre->format('d/m/Y') }}</span>
                </dd>
            </div>
            @endif
            <div class="flex justify-between items-center">
                <dt class="text-[12px] text-gray-500">Apertura</dt>
                <dd class="text-[12px] font-medium text-gray-700 tabular-nums">{{ $session->opened_at->format('d/m/Y H:i') }}</dd>
            </div>
            @if($session->notes)
            <div class="pt-2 border-t border-gray-100">
                <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Notas</dt>
                <dd class="text-[12px] text-gray-600">{{ $session->notes }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- KPI card --}}
    <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card p-5">
        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-4">Resumen del turno</p>
        <div class="space-y-4">
            <div>
                <p class="text-[11px] text-gray-400 mb-0.5">Ventas totales</p>
                <p class="text-[28px] font-black text-emerald-600 tabular-nums leading-none">{{ formatCurrency($session->total_sales) }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-3 border-t border-gray-100">
                <div>
                    <p class="text-[11px] text-gray-400 mb-1">Transacciones</p>
                    <p class="text-[20px] font-bold text-gray-900 leading-none">{{ $session->sales_count }}</p>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 mb-1">Tiempo activo</p>
                    <p class="text-[20px] font-bold text-gray-900 leading-none tabular-nums">{{ $session->duration }}h</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card p-5">
        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-4">Acciones rápidas</p>
        <div class="space-y-2">
            <a href="{{ route('sales.history') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#f4f6fb] transition-colors group">
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0 group-hover:bg-[#003594] transition-colors">
                    <svg class="w-4 h-4 text-[#003594] group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="text-[13px] font-medium text-gray-700 group-hover:text-gray-900">Historial de ventas</span>
            </a>
            <a href="{{ route('cash-movements.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#f4f6fb] transition-colors group">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0 group-hover:bg-amber-500 transition-colors">
                    <svg class="w-4 h-4 text-amber-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <span class="text-[13px] font-medium text-gray-700 group-hover:text-gray-900">Movimientos de caja</span>
            </a>
            <a href="{{ route('courtesies.create') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#f4f6fb] transition-colors group">
                <div class="w-8 h-8 rounded-lg bg-pink-50 flex items-center justify-center shrink-0 group-hover:bg-pink-500 transition-colors">
                    <svg class="w-4 h-4 text-pink-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <span class="text-[13px] font-medium text-gray-700 group-hover:text-gray-900">Registrar cortesía</span>
            </a>
        </div>
    </div>
</div>

{{-- ── Adjustments (if any) ────────────────────────────── --}}
@if($session->adjustments->isNotEmpty())
<div class="bg-amber-50 border border-amber-200/60 rounded-2xl px-5 py-4">
    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-amber-600 mb-3">Ajustes aplicados</p>
    <div class="space-y-2">
        @foreach($session->adjustments as $adj)
        <div class="flex items-center justify-between text-[12px]">
            <span class="text-amber-700 flex-1 mr-4 truncate">{{ $adj->motivo }}</span>
            <span class="font-semibold text-amber-800 tabular-nums shrink-0">
                ${{ number_format($adj->monto_anterior, 2) }} → ${{ number_format($adj->monto_nuevo, 2) }}
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Supervisor: adjust opening ──────────────────────── --}}
@if(auth()->user()->isAdminLevel() && $session->isOpen())
<div x-data="{ open: false }" class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">
    <button @click="open = !open" type="button"
        class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <span class="text-[13px] font-semibold text-gray-800">Ajustar apertura de caja</span>
        </div>
        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div x-show="open" x-transition class="px-5 pb-5 border-t border-gray-100 pt-4 space-y-3">
        <p class="text-[12px] text-amber-700 bg-amber-50 rounded-xl px-3.5 py-2.5">
            Solo supervisor/administrador · El ajuste queda auditado permanentemente.
        </p>
        <form action="{{ route('cash-sessions.adjust-opening', $session) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                    Nuevo monto de apertura <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                    <input type="number" name="monto_nuevo" step="0.01" min="0" required
                           placeholder="{{ number_format($session->opening_amount, 2) }}"
                           class="w-full pl-8 pr-4 py-3 border border-gray-200 rounded-xl text-[15px] font-semibold
                                  focus:outline-none focus:ring-2 focus:ring-amber-400/30 focus:border-amber-400 transition">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                    Motivo del ajuste <span class="text-red-500">*</span>
                </label>
                <textarea name="motivo" rows="2" required minlength="5" maxlength="500"
                          placeholder="Explica la razón del ajuste…"
                          class="w-full px-4 py-3 border border-gray-200 rounded-xl text-[13px]
                                 focus:outline-none focus:ring-2 focus:ring-amber-400/30 focus:border-amber-400 transition resize-none"></textarea>
            </div>
            <button type="submit"
                    class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[13px] rounded-xl transition">
                Aplicar ajuste
            </button>
        </form>
    </div>
</div>
@endif

{{-- ── Pending closing state ───────────────────────────── --}}
@if($session->isPendingClosing())
<div class="bg-amber-50 border border-amber-200/60 rounded-2xl px-6 py-5 flex items-start gap-4">
    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <div>
        <p class="text-[13px] font-bold text-amber-800 mb-1">Cierre pendiente de aprobación</p>
        <p class="text-[12px] text-amber-700">Tu cierre de caja está en revisión. Una vez aprobado podrás abrir una nueva sesión.</p>
    </div>
</div>
@endif

@endif

</div>
@endsection
