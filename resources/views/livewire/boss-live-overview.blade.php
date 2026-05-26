<div wire:poll.20s
     wire:loading.class.delay.long="opacity-50"
     class="space-y-4 transition-opacity duration-300">

    {{-- ── Global Money Hero ──────────────────────────────────────────────── --}}
    <div class="rounded-2xl overflow-hidden
        @if($negativeSedes > 0) glow-red border border-red-300/50
        @elseif($lowSedes > 0)  glow-amber border border-amber-300/40
        @else                   border border-white/10 shadow-card-lg
        @endif">

        {{-- 3px accent bar --}}
        <div class="h-[3px] w-full
            @if($negativeSedes > 0) bg-gradient-to-r from-red-500 via-red-600 to-rose-500
            @elseif($lowSedes > 0)  bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400
            @else                   bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-400
            @endif">
        </div>

        <div style="background:linear-gradient(135deg,#060e1f 0%,#0b1a3d 55%,#003594 100%)"
             class="flex items-stretch divide-x divide-white/10">

            {{-- Left: dominant total --}}
            <div class="flex-1 px-10 py-9 flex flex-col justify-center">
                <p style="color:rgba(255,255,255,0.35);font-size:9px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;margin-bottom:10px">
                    Red de Cajas · En Vivo
                </p>
                <p style="font-size:clamp(52px,5.5vw,80px);font-weight:800;line-height:1;letter-spacing:-.02em;
                    @if($negativeSedes > 0) color:#f87171;
                    @elseif($lowSedes > 0)  color:#fbbf24;
                    @else                   color:#fff;
                    @endif"
                   class="num-tight">
                    ${{ number_format($globalTotal, 2) }}
                </p>
                <p style="color:rgba(255,255,255,0.4);font-size:13px;margin-top:10px">
                    efectivo consolidado en {{ $sedeData->count() }} {{ $sedeData->count() === 1 ? 'caja activa' : 'cajas activas' }}
                </p>
            </div>

            {{-- Right: metric strip --}}
            <div class="flex divide-x divide-white/10 shrink-0">

                <div class="px-7 py-7 flex flex-col justify-center min-w-[130px]">
                    <p style="color:rgba(255,255,255,0.35);font-size:9px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;margin-bottom:6px">
                        Ventas hoy
                    </p>
                    <p style="color:#34d399;font-size:24px;font-weight:800;line-height:1" class="num">
                        ${{ number_format($globalSales, 0) }}
                    </p>
                    <p style="color:rgba(255,255,255,0.25);font-size:11px;margin-top:4px">efectivo</p>
                </div>

                <div class="px-7 py-7 flex flex-col justify-center min-w-[110px]">
                    <p style="color:rgba(255,255,255,0.35);font-size:9px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;margin-bottom:6px">
                        Sesiones
                    </p>
                    <p style="color:#fff;font-size:24px;font-weight:800;line-height:1">
                        {{ $sedeData->count() }}
                    </p>
                    <p style="color:rgba(255,255,255,0.25);font-size:11px;margin-top:4px">operando</p>
                </div>

                @if($lowSedes > 0 || $negativeSedes > 0)
                <div class="px-7 py-7 flex flex-col justify-center min-w-[110px]
                    @if($negativeSedes > 0) bg-red-900/20 @else bg-amber-900/20 @endif">
                    <p style="color:rgba(255,255,255,0.35);font-size:9px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;margin-bottom:6px">
                        Alertas
                    </p>
                    @if($negativeSedes > 0)
                    <p style="color:#f87171;font-size:24px;font-weight:800;line-height:1">
                        {{ $negativeSedes }}
                    </p>
                    <p style="color:rgba(248,113,113,0.6);font-size:11px;margin-top:4px">negativa{{ $negativeSedes > 1 ? 's' : '' }}</p>
                    @else
                    <p style="color:#fbbf24;font-size:24px;font-weight:800;line-height:1">
                        {{ $lowSedes }}
                    </p>
                    <p style="color:rgba(251,191,36,0.6);font-size:11px;margin-top:4px">saldo bajo</p>
                    @endif
                </div>
                @endif

                @if($pendingClosings > 0)
                <div class="px-7 py-7 flex flex-col justify-center min-w-[110px] bg-amber-900/10">
                    <p style="color:rgba(255,255,255,0.35);font-size:9px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;margin-bottom:6px">
                        Cierres
                    </p>
                    <p style="color:#fbbf24;font-size:24px;font-weight:800;line-height:1">
                        {{ $pendingClosings }}
                    </p>
                    <a href="{{ route('cash-closings.pending') }}" style="color:rgba(251,191,36,0.7);font-size:11px;margin-top:4px;text-decoration:none"
                       onmouseover="this.style.color='#fbbf24'" onmouseout="this.style.color='rgba(251,191,36,0.7)'">
                        Revisar →
                    </a>
                </div>
                @endif

            </div>
        </div>
    </div>

    {{-- ── Per-sede cards ──────────────────────────────────────────────────── --}}
    @if($sedeData->isEmpty())
        <div class="bg-white border border-gray-200/60 rounded-2xl px-6 py-12 text-center shadow-card">
            <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <p class="text-[13px] font-semibold text-gray-500">No hay sesiones de caja activas</p>
            <p class="text-[12px] text-gray-400 mt-1">Las cajas abiertas aparecerán aquí en tiempo real</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($sedeData as $item)
            @php
                $snap = $item['snapshot'];
                $sess = $item['session'];
                $isNeg = $snap['status'] === 'negativa';
                $isLow = $snap['status'] === 'baja';
                $isOk  = $snap['status'] === 'ok';
            @endphp
            <div class="bg-white rounded-2xl overflow-hidden transition-all duration-200 hover:scale-[1.01]
                @if($isNeg) glow-red border border-red-300/60
                @elseif($isLow) glow-amber border border-amber-300/50
                @else border border-gray-200/60 shadow-card
                @endif">

                {{-- 3px accent line --}}
                <div class="h-[3px]
                    @if($isNeg) bg-gradient-to-r from-red-500 via-red-600 to-rose-500
                    @elseif($isLow) bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400
                    @else bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-400
                    @endif">
                </div>

                {{-- Header bar --}}
                <div class="flex items-center justify-between px-4 py-2.5
                    @if($isNeg) bg-red-50/60 border-b border-red-100/80
                    @elseif($isLow) bg-amber-50/60 border-b border-amber-100/70
                    @else bg-emerald-50/40 border-b border-emerald-100/50
                    @endif">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2 w-2 shrink-0">
                            <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full
                                @if($isNeg) bg-red-400 @elseif($isLow) bg-amber-400 @else bg-emerald-400 @endif">
                            </span>
                            <span class="relative inline-flex rounded-full h-2 w-2
                                @if($isNeg) bg-red-500 @elseif($isLow) bg-amber-400 @else bg-emerald-500 @endif">
                            </span>
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.12em]
                            @if($isNeg) text-red-700 @elseif($isLow) text-amber-700 @else text-emerald-700 @endif">
                            @if($isNeg) Déficit @elseif($isLow) Saldo bajo @else Operativa @endif
                        </span>
                    </div>
                    <span class="text-[10px] font-mono text-gray-400">{{ $snap['session_duration'] }}h</span>
                </div>

                <div class="px-5 py-4">
                    {{-- Sede + Operator --}}
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-[14px] font-bold text-gray-900 leading-none">{{ $sess->sede?->nombre ?? '—' }}</p>
                            <p class="text-[11px] text-gray-400 mt-1">{{ $sess->user?->name ?? '—' }}</p>
                        </div>
                        @if($sess->status === 'pending_closing')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 border border-amber-200/80 text-[9px] font-bold text-amber-700">
                            Cierre enviado
                        </span>
                        @endif
                    </div>

                    {{-- Balance dominant --}}
                    <p class="font-bold leading-none num-tight mb-4
                        @if($isNeg) text-red-600 @elseif($isLow) text-amber-600 @else text-gray-900 @endif"
                       style="font-size:clamp(28px,3.5vw,40px)">
                        ${{ number_format($snap['total'], 2) }}
                    </p>

                    {{-- Mini ledger --}}
                    <div class="space-y-1.5 text-[11px] border-t border-gray-100 pt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 flex items-center gap-1.5">
                                <span class="w-1 h-1 rounded-full bg-blue-400 shrink-0"></span>Apertura
                            </span>
                            <span class="font-medium text-gray-600 num">${{ number_format($snap['opening'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 flex items-center gap-1.5">
                                <span class="w-1 h-1 rounded-full bg-emerald-500 shrink-0"></span>Ventas
                            </span>
                            <span class="font-semibold text-emerald-600 num">+${{ number_format($snap['ventas_efectivo'], 2) }}</span>
                        </div>
                        @if($snap['ingresos'] > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 flex items-center gap-1.5">
                                <span class="w-1 h-1 rounded-full bg-teal-400 shrink-0"></span>Ingresos
                            </span>
                            <span class="font-semibold text-teal-600 num">+${{ number_format($snap['ingresos'], 2) }}</span>
                        </div>
                        @endif
                        @if($snap['egresos'] > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 flex items-center gap-1.5">
                                <span class="w-1 h-1 rounded-full bg-amber-500 shrink-0"></span>Egresos
                            </span>
                            <span class="font-semibold text-amber-600 num">-${{ number_format($snap['egresos'], 2) }}</span>
                        </div>
                        @endif
                        @if($snap['valor_cortesias'] > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 flex items-center gap-1.5">
                                <span class="w-1 h-1 rounded-full bg-pink-400 shrink-0"></span>Cortesías
                            </span>
                            <span class="font-semibold text-pink-500 num">-${{ number_format($snap['valor_cortesias'], 2) }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    @if($snap['pending_mov'] + $snap['pending_cor'] > 0)
                    <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                        <span class="text-[10px] font-semibold text-amber-600">
                            {{ $snap['pending_mov'] + $snap['pending_cor'] }} pendiente{{ ($snap['pending_mov'] + $snap['pending_cor']) > 1 ? 's' : '' }} de aprobación
                        </span>
                    </div>
                    @endif
                </div>

            </div>
            @endforeach
        </div>
    @endif

</div>
