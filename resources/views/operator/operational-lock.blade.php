<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso Suspendido — STOCK365</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans antialiased min-h-screen flex items-center justify-center px-4 py-8

    {{-- Severity-based background tint --}}
    {{ $issue->isCritical() ? 'bg-red-50/60' : 'bg-amber-50/40' }}

    " style="background-color: #f0f2f8;">

{{-- ══════════════════════════════════════════════════════════════
     OPERATIONAL LOCK SCREEN
══════════════════════════════════════════════════════════════ --}}

@php
    $isCritical     = $issue->isCritical();
    $isWarning      = $issue->isWarning();
    $color          = $issue->color();               // 'amber' | 'red'

    // Severity-driven CSS tokens
    $accentGradient = $isCritical
        ? 'bg-gradient-to-r from-red-500 to-rose-500'
        : 'bg-gradient-to-r from-amber-400 to-orange-400';

    $cardBorder     = $isCritical ? 'border-red-200/70' : 'border-amber-200/60';
    $cardGlow       = $isCritical ? 'shadow-[0_0_0_1px_rgba(239,68,68,0.2),0_8px_40px_rgba(239,68,68,0.10)]' : 'shadow-[0_0_0_1px_rgba(245,158,11,0.22),0_8px_40px_rgba(245,158,11,0.10)]';

    $iconBg         = $isCritical ? 'bg-red-100'   : 'bg-amber-100';
    $iconColor      = $isCritical ? 'text-red-500'  : 'text-amber-500';
    $iconRing       = $isCritical ? 'ring-red-200'  : 'ring-amber-200';

    $severityBg     = $isCritical ? 'bg-red-100 text-red-700 border-red-200'     : 'bg-amber-100 text-amber-700 border-amber-200';
    $severityDot    = $isCritical ? 'bg-red-500' : 'bg-amber-400';

    $btnEscalate    = $isCritical
        ? 'bg-red-600 hover:bg-red-700 text-white border-red-700 focus:ring-red-500'
        : 'bg-amber-500 hover:bg-amber-600 text-white border-amber-600 focus:ring-amber-400';

    $detailBg       = $isCritical ? 'bg-red-50/60'   : 'bg-amber-50/60';
    $detailBorder   = $isCritical ? 'border-red-100'  : 'border-amber-100';
    $detailLabel    = $isCritical ? 'text-red-400'    : 'text-amber-500';

    $sessionDuration = $issue->session->duration;
    $openedAt        = $issue->session->opened_at;
    $sedeName        = $issue->session->sede?->nombre ?? '—';
@endphp

<div class="w-full max-w-[460px]" x-data="lockScreen()">

    {{-- ── Card ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border {{ $cardBorder }} {{ $cardGlow }} overflow-hidden animate-feed-in">

        {{-- Top severity accent line --}}
        <div class="h-1 {{ $accentGradient }}"></div>

        {{-- ── Header ─────────────────────────────────────────────────── --}}
        <div class="px-8 pt-8 pb-6 text-center">

            {{-- Animated lock icon --}}
            <div class="relative mx-auto mb-5 w-fit">
                <div class="w-16 h-16 {{ $iconBg }} {{ $iconRing }} ring-4 ring-offset-2 rounded-2xl flex items-center justify-center {{ $isCritical ? 'animate-badge-pulse' : '' }}">
                    <svg class="w-8 h-8 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                {{-- Live pulse ring for critical --}}
                @if($isCritical)
                <span class="absolute -top-1 -right-1 flex h-4 w-4">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 items-center justify-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                    </span>
                </span>
                @endif
            </div>

            <h1 class="text-[18px] font-bold text-gray-900 tracking-tight leading-snug">
                Acceso Operacional Suspendido
            </h1>
            <p class="text-[13px] text-gray-500 mt-1.5 leading-relaxed max-w-xs mx-auto">
                El sistema detectó una inconsistencia operacional que requiere revisión.
            </p>
        </div>

        {{-- ── Issue details panel ──────────────────────────────────── --}}
        <div class="mx-6 mb-5 rounded-xl {{ $detailBg }} border {{ $detailBorder }} overflow-hidden">

            {{-- Severity row --}}
            <div class="flex items-center justify-between px-4 py-3 border-b {{ $detailBorder }}">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] {{ $detailLabel }}">Severidad</p>
                <div class="flex items-center gap-1.5">
                    <span class="w-[6px] h-[6px] rounded-full {{ $severityDot }} {{ $isCritical ? 'animate-badge-pulse' : '' }}"></span>
                    <span class="text-[10px] font-bold uppercase tracking-[0.10em] px-2 py-[3px] rounded-md border {{ $severityBg }}">
                        {{ $issue->severityLabel() }}
                    </span>
                </div>
            </div>

            {{-- Operator --}}
            <div class="flex items-center justify-between px-4 py-2.5 border-b {{ $detailBorder }}">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">Operador</p>
                <p class="text-[13px] font-semibold text-gray-800">{{ $user->name }}</p>
            </div>

            {{-- Sede --}}
            <div class="flex items-center justify-between px-4 py-2.5 border-b {{ $detailBorder }}">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">Sede</p>
                <p class="text-[13px] font-semibold text-gray-800">{{ $sedeName }}</p>
            </div>

            {{-- Session start --}}
            <div class="flex items-center justify-between px-4 py-2.5 border-b {{ $detailBorder }}">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">Sesión iniciada</p>
                <p class="text-[13px] font-semibold text-gray-700 tabular-nums">
                    {{ $openedAt->isToday()
                        ? 'Hoy ' . $openedAt->format('H:i')
                        : $openedAt->locale('es')->isoFormat('ddd D MMM, H:mm') }}
                </p>
            </div>

            {{-- Duration --}}
            <div class="flex items-center justify-between px-4 py-2.5 border-b {{ $detailBorder }}">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">Duración</p>
                <p class="text-[13px] font-bold tabular-nums {{ $isCritical ? 'text-red-500' : 'text-amber-600' }}">
                    {{ $sessionDuration }}
                </p>
            </div>

            {{-- Motivo --}}
            <div class="px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1.5">Motivo</p>
                <p class="text-[12px] text-gray-700 leading-relaxed">{{ $issue->message() }}</p>
            </div>
        </div>

        {{-- ── Resolution guidance ────────────────────────────────── --}}
        <div class="mx-6 mb-5 flex items-start gap-2.5 px-4 py-3 rounded-xl bg-gray-50 border border-gray-100">
            <svg class="w-4 h-4 text-gray-400 shrink-0 mt-[1px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-[12px] text-gray-600 leading-relaxed">{{ $issue->resolution() }}</p>
        </div>

        {{-- ── Action buttons ──────────────────────────────────────── --}}
        <div class="px-6 pb-7 space-y-2.5">

            {{-- Escalate to supervisor --}}
            <div x-show="!escalated">
                <form method="POST" action="{{ route('operational.lock.escalate') }}" @submit="escalated = true">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-[13px] border transition-all duration-[120ms] focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $btnEscalate }}"
                            @click="escalated = true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Solicitar autorización a supervisor
                    </button>
                </form>
            </div>

            {{-- Escalation confirmation state --}}
            @if($escalationSent || session('operational_lock_escalated'))
            <div class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700">
                <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <div class="flex-1">
                    <p class="text-[12px] font-semibold text-emerald-800">Solicitud enviada</p>
                    <p class="text-[11px] text-emerald-600">
                        @if($escalationCooldown > 0)
                            Puedes reenviar en {{ $escalationCooldown }} min.
                        @else
                            El supervisor ha sido notificado.
                        @endif
                    </p>
                </div>
            </div>
            @endif

            {{-- View caja status (only for WARNING — pending closing has a status to check) --}}
            @if($issue->isWarning())
            <a href="{{ route('cash-session.status') }}"
               class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-[13px] text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-all duration-[120ms]">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Ver estado de caja
            </a>
            @endif

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-medium text-[13px] text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-all duration-[120ms]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar sesión
                </button>
            </form>

        </div>
    </div>

    {{-- ── Footer branding ─────────────────────────────────────────── --}}
    <div class="mt-5 text-center space-y-1">
        <p class="text-[11px] font-semibold text-gray-400">
            STOCK<span class="text-[#003594]">365</span>
            <span class="text-gray-300 mx-1.5">·</span>
            Operational Integrity Protection System
        </p>
        <p class="text-[10px] text-gray-300 tabular-nums">
            {{ now()->format('d/m/Y H:i:s') }}
        </p>
    </div>

</div>

<script>
function lockScreen() {
    return {
        escalated: {{ ($escalationSent || session('operational_lock_escalated')) ? 'true' : 'false' }},
    };
}
</script>

@livewireScripts
</body>
</html>
