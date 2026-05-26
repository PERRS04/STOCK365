<div wire:poll.15s
     wire:loading.class.delay.long="opacity-50"
     class="rounded-2xl overflow-hidden transition-opacity duration-300
    @if(!$session) border border-white/10 shadow-card-lg
    @elseif($snapshot['status'] === 'ok') glow-emerald border border-emerald-200/40
    @elseif($snapshot['status'] === 'baja') glow-amber border border-amber-300/50
    @else glow-red border border-red-300/60
    @endif">

    @if(!$session)

        {{-- ── NO SESSION: Command center sleeping ──────────────────────── --}}
        <div style="background:linear-gradient(135deg,#060e1f 0%,#0b1a3d 45%,#003594 100%);min-height:148px"
             class="flex items-center justify-between px-10 py-9">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0"
                     style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12)">
                    <svg class="w-7 h-7" fill="none" stroke="rgba(255,255,255,0.35)" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <p style="color:rgba(255,255,255,0.35);font-size:9px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;margin-bottom:6px">
                        Control de Caja · Sistema
                    </p>
                    <p style="color:#fff;font-size:18px;font-weight:800;line-height:1.2;letter-spacing:-.01em">
                        Sin sesión de caja activa
                    </p>
                    <p style="color:rgba(255,255,255,0.4);font-size:13px;margin-top:6px">
                        Inicia turno para activar el control de caja en tiempo real
                    </p>
                </div>
            </div>
            <a href="{{ route('cash-session.create') }}"
               style="background:#FFD100;color:#003594;padding:12px 24px;border-radius:12px;font-weight:800;font-size:13px;letter-spacing:.02em;display:inline-flex;align-items:center;gap:8px;flex-shrink:0;text-decoration:none;transition:opacity .15s"
               onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                Abrir caja
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

    @else

        {{-- ── 3px gradient accent ────────────────────────────────────────── --}}
        <div class="h-[3px] w-full
            @if($snapshot['status'] === 'ok') bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-400
            @elseif($snapshot['status'] === 'baja') bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400
            @else bg-gradient-to-r from-red-500 via-red-600 to-rose-500
            @endif">
        </div>

        {{-- ── Status bar ─────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between px-8 py-2.5 border-b
            @if($snapshot['status'] === 'ok') bg-emerald-50/60 border-emerald-100/70
            @elseif($snapshot['status'] === 'baja') bg-amber-50/70 border-amber-100/80
            @else bg-red-50/70 border-red-100/90
            @endif">

            <div class="flex items-center gap-2.5">
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full
                        @if($snapshot['status'] === 'ok') bg-emerald-400
                        @elseif($snapshot['status'] === 'baja') bg-amber-400
                        @else bg-red-500
                        @endif"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2
                        @if($snapshot['status'] === 'ok') bg-emerald-500
                        @elseif($snapshot['status'] === 'baja') bg-amber-400
                        @else bg-red-500
                        @endif"></span>
                </span>
                <span class="text-[10px] font-bold uppercase tracking-[0.13em]
                    @if($snapshot['status'] === 'ok') text-emerald-800
                    @elseif($snapshot['status'] === 'baja') text-amber-800
                    @else text-red-800
                    @endif">
                    @if($snapshot['status'] === 'ok') Caja operativa
                    @elseif($snapshot['status'] === 'baja') Saldo bajo — verificar
                    @else Déficit de caja — acción requerida
                    @endif
                </span>
                <span class="text-[10px] text-gray-400/80 font-mono">
                    · Desde {{ $snapshot['opened_at'] instanceof \Carbon\Carbon
                        ? $snapshot['opened_at']->format('H:i')
                        : \Carbon\Carbon::parse($snapshot['opened_at'])->format('H:i') }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                @if($session->status === 'pending_closing')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 border border-amber-200/80 text-amber-700 text-[10px] font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                        Cierre pendiente aprobación
                    </span>
                @endif
                <span class="text-[10px] font-mono text-gray-400">{{ $snapshot['session_duration'] }}h activa</span>
                <span class="text-[10px] font-mono text-gray-300 tabular-nums">{{ now()->format('H:i') }}</span>
            </div>
        </div>

        {{-- ── Main body: Balance + Ledger ───────────────────────────────── --}}
        <div class="bg-white flex items-stretch divide-x divide-gray-100">

            {{-- Left: Dominant balance ──────────────────────────────────── --}}
            <div style="width:42%" class="px-8 py-8 flex flex-col justify-center">

                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400 mb-3">
                    Total en Caja · Sistema
                </p>

                <p class="font-bold leading-none num-tight
                    @if($snapshot['status'] === 'ok') text-gray-900
                    @elseif($snapshot['status'] === 'baja') text-amber-600
                    @else text-red-600
                    @endif"
                   style="font-size:clamp(52px,6.5vw,92px)">
                    ${{ number_format($snapshot['total'], 2) }}
                </p>

                @php
                    $neto = $snapshot['ventas_efectivo']
                          + $snapshot['ingresos']
                          - $snapshot['egresos']
                          - $snapshot['valor_cortesias'];
                @endphp

                <div class="mt-6 pt-5 border-t border-gray-100 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] text-gray-400 w-32 shrink-0">Neto operaciones</span>
                        <span class="text-[13px] font-bold num {{ $neto >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $neto >= 0 ? '+' : '' }}${{ number_format($neto, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] text-gray-400 w-32 shrink-0">Fondo apertura</span>
                        <span class="text-[13px] font-semibold text-gray-500 num">
                            ${{ number_format($snapshot['opening'], 2) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Right: Financial ledger ──────────────────────────────────── --}}
            <div class="flex-1 px-8 py-7">

                <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-gray-400 mb-4">
                    Composición de Caja
                </p>

                <div class="space-y-0">

                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0"></span>
                            <span class="text-[12px] text-gray-500">Fondo apertura</span>
                        </div>
                        <span class="text-[13px] font-semibold text-gray-600 num">
                            ${{ number_format($snapshot['opening'], 2) }}
                        </span>
                    </div>

                    <div class="border-t border-dashed border-gray-100/80"></div>

                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="text-[12px] text-gray-700 font-medium">Ventas efectivo</span>
                        </div>
                        <span class="text-[13px] font-bold text-emerald-600 num">
                            +${{ number_format($snapshot['ventas_efectivo'], 2) }}
                        </span>
                    </div>

                    @if($snapshot['ingresos'] > 0)
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-400 shrink-0"></span>
                            <span class="text-[12px] text-gray-700">Cambio / ingresos</span>
                        </div>
                        <span class="text-[13px] font-bold text-teal-600 num">
                            +${{ number_format($snapshot['ingresos'], 2) }}
                        </span>
                    </div>
                    @endif

                    @if($snapshot['egresos'] > 0 || $snapshot['valor_cortesias'] > 0)
                    <div class="border-t border-dashed border-gray-100/80"></div>
                    @endif

                    @if($snapshot['egresos'] > 0)
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                            <span class="text-[12px] text-gray-700">Egresos / depósitos</span>
                        </div>
                        <span class="text-[13px] font-bold text-amber-600 num">
                            -${{ number_format($snapshot['egresos'], 2) }}
                        </span>
                    </div>
                    @endif

                    @if($snapshot['valor_cortesias'] > 0)
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-pink-400 shrink-0"></span>
                            <span class="text-[12px] text-gray-700">Cortesías</span>
                        </div>
                        <span class="text-[13px] font-bold text-pink-500 num">
                            -${{ number_format($snapshot['valor_cortesias'], 2) }}
                        </span>
                    </div>
                    @endif

                    <div class="border-t-2 border-gray-200 mt-2 pt-3.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-gray-500">
                                = Total en caja
                            </span>
                            <span class="text-[18px] font-bold num
                                @if($snapshot['status'] === 'ok') text-gray-900
                                @elseif($snapshot['status'] === 'baja') text-amber-700
                                @else text-red-700
                                @endif">
                                ${{ number_format($snapshot['total'], 2) }}
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Pending footer ─────────────────────────────────────────────── --}}
        @if($snapshot['pending_mov'] > 0 || $snapshot['pending_cor'] > 0)
        <div class="px-8 py-3 border-t border-gray-100 bg-gray-50/80 flex items-center gap-5">
            <span class="text-[9px] font-bold uppercase tracking-[0.14em] text-gray-400">
                Pendientes de aprobación
            </span>
            @if($snapshot['pending_mov'] > 0)
            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                {{ $snapshot['pending_mov'] }} movimiento{{ $snapshot['pending_mov'] > 1 ? 's' : '' }}
            </span>
            @endif
            @if($snapshot['pending_cor'] > 0)
            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-pink-700">
                <span class="w-1.5 h-1.5 rounded-full bg-pink-400 shrink-0"></span>
                {{ $snapshot['pending_cor'] }} cortesía{{ $snapshot['pending_cor'] > 1 ? 's' : '' }}
            </span>
            @endif
        </div>
        @endif

    @endif
</div>
