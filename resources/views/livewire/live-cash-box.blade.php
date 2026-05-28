{{--
  Live Cash Box — Financial engine widget.
  Alpine $wire.$watch observes public Livewire props for animated transitions.
  wire:poll.keep-alive stops polling when the browser tab is hidden.
--}}
<div wire:poll.15s.keep-alive
     wire:loading.class.delay.long="opacity-50"
     class="rounded-2xl overflow-hidden transition-opacity duration-300
         {{ !$hasSession    ? 'border border-white/10 shadow-card-lg' :
           ($riskLevel === 'negative' ? 'glow-red border border-red-300/60' :
           ($riskLevel === 'low_balance' || $riskLevel === 'stale_pending'
                                       ? 'glow-amber border border-amber-300/50'
                                       :  'glow-emerald border border-emerald-200/40')) }}"
     x-data="liveCashBox({{ $totalInBox }}, {{ $ventas }}, {{ $egresos }}, {{ $ingresos }}, {{ $cortesias }})">

    {{-- ═══════════════════════════════════════════════════════════════════════
         STATE A — No session (sleeping engine)
    ═══════════════════════════════════════════════════════════════════════ --}}
    @if(!$hasSession)
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
                    Live Cash Engine · Sistema
                </p>
                <p style="color:#fff;font-size:18px;font-weight:800;line-height:1.2;letter-spacing:-.01em">
                    Sin sesión de caja activa
                </p>
                <p style="color:rgba(255,255,255,0.4);font-size:13px;margin-top:6px">
                    Inicia turno para activar el motor de caja en tiempo real
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

    {{-- ═══════════════════════════════════════════════════════════════════════
         STATE B — Live session
    ═══════════════════════════════════════════════════════════════════════ --}}

    {{-- 3px accent bar --}}
    <div class="h-[3px] w-full {{ $risk->accentBar() }}
        {{ $risk->isCritical() ? 'animate-pulse' : '' }}">
    </div>

    {{-- Status bar --}}
    <div class="flex items-center justify-between px-8 py-2.5 border-b {{ $risk->statusClass() }}">
        <div class="flex items-center gap-2.5">
            <span class="relative flex h-2 w-2 shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full {{ $risk->ringClass() }}"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 {{ $risk->dotClass() }}"></span>
            </span>
            <span class="text-[10px] font-bold uppercase tracking-[0.13em]">{{ $risk->label() }}</span>
            <span class="text-[10px] text-gray-400/80 font-mono">· Desde {{ $openedAtFmt }}</span>
        </div>
        <div class="flex items-center gap-3">
            @if($sessionStatus === 'pending_closing')
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 border border-amber-200/80 text-amber-700 text-[10px] font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                Cierre pendiente aprobación
            </span>
            @endif
            <span class="text-[10px] font-mono text-gray-400">{{ $duration }}h activa</span>
            <span class="text-[10px] font-mono text-gray-300 tabular-nums">{{ now()->format('H:i') }}</span>
        </div>
    </div>

    {{-- Main body: Animated balance + ledger --}}
    <div class="bg-white flex items-stretch divide-x divide-gray-100">

        {{-- ── Left: Dominant animated balance ────────────────────────────── --}}
        <div style="width:42%" class="px-8 py-8 flex flex-col justify-center">

            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-gray-400 mb-3">
                Total en Caja · Tiempo Real
            </p>

            {{-- Animated balance with direction arrow --}}
            <div class="flex items-baseline gap-3">
                <p class="font-bold leading-none num-tight transition-colors duration-700 {{ $risk->balanceClr() }}"
                   style="font-size:clamp(52px,6.5vw,92px)"
                   x-text="fmt(disp.total)">
                </p>
                {{-- Direction indicator (fades after 3.5s) --}}
                <div x-show="dir" x-cloak
                     class="flex flex-col items-center gap-0.5 animate-feed-in">
                    <span class="text-[20px] font-bold leading-none transition-colors"
                          :class="dir === 'up' ? 'text-emerald-500' : 'text-red-400'"
                          x-text="dir === 'up' ? '↑' : '↓'"></span>
                    <span class="text-[10px] font-bold tabular-nums"
                          :class="dir === 'up' ? 'text-emerald-400' : 'text-red-400'"
                          x-text="(dir === 'up' ? '+' : '') + fmtDelta(disp.total - prev.total)">
                    </span>
                </div>
            </div>

            {{-- Net operations sub-metric --}}
            <div class="mt-6 pt-5 border-t border-gray-100 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-gray-400 w-32 shrink-0">Neto operaciones</span>
                    <span class="text-[13px] font-bold num tabular-nums"
                          :class="(disp.ventas + disp.ingresos - disp.egresos - disp.cortesias) >= 0 ? 'text-emerald-600' : 'text-red-600'"
                          x-text="(disp.ventas + disp.ingresos - disp.egresos - disp.cortesias) >= 0
                              ? '+$' + fmtAmt(disp.ventas + disp.ingresos - disp.egresos - disp.cortesias)
                              : '-$' + fmtAmt(Math.abs(disp.ventas + disp.ingresos - disp.egresos - disp.cortesias))">
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-gray-400 w-32 shrink-0">Fondo apertura</span>
                    <span class="text-[13px] font-semibold text-gray-500 num tabular-nums"
                          x-text="'$' + fmtAmt(disp.opening)">
                    </span>
                </div>
            </div>
        </div>

        {{-- ── Right: Financial ledger ──────────────────────────────────────── --}}
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
                    <span class="text-[13px] font-semibold text-gray-600 num tabular-nums"
                          x-text="'$' + fmtAmt(disp.opening)"></span>
                </div>

                <div class="border-t border-dashed border-gray-100/80"></div>

                <div class="flex items-center justify-between py-2.5">
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                        <span class="text-[12px] text-gray-700 font-medium">Ventas efectivo</span>
                    </div>
                    <span class="text-[13px] font-bold text-emerald-600 num tabular-nums"
                          x-text="'+$' + fmtAmt(disp.ventas)"></span>
                </div>

                <div x-show="disp.ingresos > 0" class="flex items-center justify-between py-2.5">
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-400 shrink-0"></span>
                        <span class="text-[12px] text-gray-700">Cambio / ingresos</span>
                    </div>
                    <span class="text-[13px] font-bold text-teal-600 num tabular-nums"
                          x-text="'+$' + fmtAmt(disp.ingresos)"></span>
                </div>

                <div x-show="disp.egresos > 0 || disp.cortesias > 0"
                     class="border-t border-dashed border-gray-100/80"></div>

                <div x-show="disp.egresos > 0" class="flex items-center justify-between py-2.5">
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                        <span class="text-[12px] text-gray-700">Egresos / depósitos</span>
                    </div>
                    <span class="text-[13px] font-bold text-amber-600 num tabular-nums"
                          x-text="'-$' + fmtAmt(disp.egresos)"></span>
                </div>

                <div x-show="disp.cortesias > 0" class="flex items-center justify-between py-2.5">
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-pink-400 shrink-0"></span>
                        <span class="text-[12px] text-gray-700">Cortesías</span>
                    </div>
                    <span class="text-[13px] font-bold text-pink-500 num tabular-nums"
                          x-text="'-$' + fmtAmt(disp.cortesias)"></span>
                </div>

                <div class="border-t-2 border-gray-200 mt-2 pt-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-gray-500">
                            = Total en caja
                        </span>
                        <span class="text-[18px] font-bold num tabular-nums transition-colors duration-700 {{ $risk->balanceClr() }}"
                              x-text="fmt(disp.total)"></span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Pending footer (action required) --}}
    @if($pendingMov > 0 || $pendingCor > 0)
    <div class="px-8 py-3 border-t border-amber-100/80 bg-amber-50/60 flex items-center gap-5">
        <span class="relative flex h-[5px] w-[5px] shrink-0">
            <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-amber-400"></span>
            <span class="relative inline-flex rounded-full h-[5px] w-[5px] bg-amber-400"></span>
        </span>
        <span class="text-[9px] font-bold uppercase tracking-[0.14em] text-amber-600">
            Esperando aprobación
        </span>
        @if($pendingMov > 0)
        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700">
            {{ $pendingMov }} movimiento{{ $pendingMov > 1 ? 's' : '' }}
        </span>
        @endif
        @if($pendingCor > 0)
        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-pink-700">
            {{ $pendingCor }} cortesía{{ $pendingCor > 1 ? 's' : '' }}
        </span>
        @endif
    </div>
    @endif

    @endif

</div>

<script>
function liveCashBox(initTotal, initVentas, initEgresos, initIngresos, initCortesias) {
    return {
        disp: {
            total:     initTotal,
            ventas:    initVentas,
            egresos:   initEgresos,
            ingresos:  initIngresos,
            cortesias: initCortesias,
            opening:   {{ $opening ?? 0 }},
        },
        prev: {
            total:  initTotal,
        },
        dir:    null,
        _timer: null,

        init() {
            const props = {
                totalInBox: 'total',
                ventas:     'ventas',
                egresos:    'egresos',
                ingresos:   'ingresos',
                cortesias:  'cortesias',
                opening:    'opening',
            };

            Object.entries(props).forEach(([wireProp, dispKey]) => {
                this.$watch(() => this.$wire[wireProp], (n, o) => {
                    n = Number(n) || 0;
                    o = Number(o) || 0;
                    if (Math.abs(n - o) < 0.001) return;

                    if (wireProp === 'totalInBox') {
                        this.prev.total = o;
                        this.dir = n > o ? 'up' : 'down';
                        clearTimeout(this._timer);
                        this._timer = setTimeout(() => this.dir = null, 3500);
                    }

                    this.morphTo(dispKey, o, n);
                });
            });
        },

        morphTo(key, from, to) {
            const dur = 850, t0 = performance.now();
            const step = (t) => {
                const p = Math.min((t - t0) / dur, 1);
                const e = 1 - Math.pow(1 - p, 3);
                this.disp[key] = from + (to - from) * e;
                p < 1 ? requestAnimationFrame(step) : (this.disp[key] = to);
            };
            requestAnimationFrame(step);
        },

        fmt(v) {
            const neg = v < 0;
            return (neg ? '-' : '') + '$' + Math.abs(v).toLocaleString('en-US', {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
        },

        fmtAmt(v) {
            return Math.abs(v).toLocaleString('en-US', {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
        },

        fmtDelta(v) {
            return Math.abs(v).toLocaleString('en-US', {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
        },
    };
}
</script>
