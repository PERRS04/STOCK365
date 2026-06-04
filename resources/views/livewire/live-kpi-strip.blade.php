{{--
    LiveKpiStrip — animated KPI strip with Alpine count-up on Livewire updates.
    wire:poll.keep-alive stops polling when the browser tab is hidden.
    Alpine $wire.$watch() detects property changes post-hydration and animates.
--}}
<div wire:poll.30s.keep-alive
     wire:loading.class.delay.long="opacity-70"
     class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden transition-opacity duration-500"
     x-data="kpiStrip()">

    {{-- Brand gradient accent --}}
    <div class="h-[3px] bg-gradient-to-r from-stock-primary via-blue-500 to-indigo-400"></div>

    <div class="grid {{ $isBoss ? 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-7' : 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6' }} divide-x divide-gray-100/80">

        {{-- ── HERO: Ventas Hoy ─────────────────────────────────────────────── --}}
        <div class="px-6 py-5 relative">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Ventas Hoy</p>

            <div class="flex items-baseline gap-2">
                <p class="text-[28px] font-extrabold leading-none tabular-nums tracking-tight transition-colors duration-700"
                   :class="dirs.totalSalesToday === 'up'   ? 'text-emerald-600' :
                           dirs.totalSalesToday === 'down' ? 'text-red-500' : 'text-gray-900'"
                   x-text="'$' + vals.totalSalesToday.toLocaleString('en-US', {maximumFractionDigits:0})">
                </p>
                <span x-show="dirs.totalSalesToday"
                      x-cloak
                      class="text-[11px] font-bold transition-all duration-300 animate-feed-in"
                      :class="dirs.totalSalesToday === 'up' ? 'text-emerald-500' : 'text-red-400'"
                      x-text="dirs.totalSalesToday === 'up' ? '↑' : '↓'">
                </span>
            </div>
            <p class="text-[11px] text-gray-400 mt-2 tabular-nums"
               x-text="vals.transactionCount + ' transacciones'">
            </p>
        </div>

        {{-- ── Ventas Mes ──────────────────────────────────────────────────── --}}
        <div class="px-5 py-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Ventas Mes</p>
            <p class="text-[20px] font-bold text-gray-900 leading-none tabular-nums"
               x-text="'$' + vals.totalSalesMonth.toLocaleString('en-US', {maximumFractionDigits:0})">
            </p>
            <p class="text-[11px] text-gray-400 mt-2">{{ now()->locale('es')->isoFormat('MMMM') }}</p>
        </div>

        {{-- ── Utilidad (boss only) ────────────────────────────────────────── --}}
        @if($isBoss)
        <div class="px-5 py-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Utilidad</p>
            <p class="text-[20px] font-bold leading-none tabular-nums transition-colors duration-700"
               :class="vals.utilidadHoy > 0 ? 'text-emerald-600' : 'text-gray-700'"
               x-text="'$' + vals.utilidadHoy.toLocaleString('en-US', {maximumFractionDigits:0})">
            </p>
            <p class="text-[11px] text-gray-400 mt-2">Margen bruto</p>
        </div>
        @endif

        {{-- ── Ticket Promedio ─────────────────────────────────────────────── --}}
        <div class="px-5 py-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Ticket Prom.</p>
            <p class="text-[20px] font-bold text-gray-900 leading-none tabular-nums"
               x-text="'$' + vals.avgTicket.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})">
            </p>
            <p class="text-[11px] text-gray-400 mt-2">Por transacción</p>
        </div>

        {{-- ── Cierres Pendientes ──────────────────────────────────────────── --}}
        <div class="px-5 py-5 transition-colors duration-700"
             :class="vals.pendingClosings > 0 ? 'bg-amber-50/60' : ''">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] mb-2.5 transition-colors duration-500"
               :class="vals.pendingClosings > 0 ? 'text-amber-500' : 'text-gray-400'">Cierres</p>
            <p class="text-[20px] font-bold leading-none tabular-nums transition-colors duration-500"
               :class="vals.pendingClosings > 0 ? 'text-amber-500' : 'text-gray-300'"
               x-text="vals.pendingClosings">
            </p>
            <template x-if="vals.pendingClosings > 0">
                <a href="{{ route('cash-closings.pending') }}"
                   class="text-[11px] text-amber-600 hover:underline mt-2 inline-flex items-center gap-0.5 font-semibold">
                    Revisar
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            </template>
            <template x-if="vals.pendingClosings === 0">
                <p class="text-[11px] text-emerald-500 font-semibold mt-2">Al día</p>
            </template>
        </div>

        {{-- ── Alertas Stock ───────────────────────────────────────────────── --}}
        <div class="px-5 py-5 transition-colors duration-700"
             :class="vals.alertCount > 0 ? 'bg-red-50/40' : ''">
            <div class="flex items-center gap-1.5 mb-2.5">
                <span x-show="vals.alertCount > 0" x-cloak
                      class="relative flex h-[5px] w-[5px] shrink-0">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400"></span>
                    <span class="relative inline-flex rounded-full h-[5px] w-[5px] bg-red-500"></span>
                </span>
                <p class="text-[9px] font-bold uppercase tracking-[0.15em] transition-colors duration-500"
                   :class="vals.alertCount > 0 ? 'text-red-500' : 'text-gray-400'">Alertas</p>
            </div>
            <p class="text-[20px] font-bold leading-none tabular-nums transition-colors duration-500"
               :class="vals.alertCount > 0 ? 'text-red-500' : 'text-gray-300'"
               x-text="vals.alertCount">
            </p>
            <template x-if="vals.alertCount > 0">
                <a href="{{ route('inventory.index') }}"
                   class="text-[11px] text-red-500 hover:underline mt-2 inline-flex items-center gap-0.5 font-semibold">
                    Ver stock
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            </template>
            <template x-if="vals.alertCount === 0">
                <p class="text-[11px] text-emerald-500 font-semibold mt-2">Normal</p>
            </template>
        </div>

        {{-- ── Cajas Activas ───────────────────────────────────────────────── --}}
        <div class="px-5 py-5 transition-colors duration-700"
             :class="vals.activeSessionsCount > 0 ? 'bg-emerald-50/30' : ''">
            <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 mb-2.5">Cajas</p>
            <div class="flex items-center gap-2">
                <span x-show="vals.activeSessionsCount > 0" x-cloak
                      class="relative flex h-[7px] w-[7px] shrink-0">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                    <span class="relative inline-flex rounded-full h-[7px] w-[7px] bg-emerald-500"></span>
                </span>
                <p class="text-[20px] font-bold leading-none tabular-nums transition-colors duration-500"
                   :class="vals.activeSessionsCount > 0 ? 'text-emerald-600' : 'text-gray-300'"
                   x-text="vals.activeSessionsCount">
                </p>
            </div>
            <a href="{{ route('cash-sessions.index') }}"
               class="text-[11px] text-gray-400 hover:text-stock-primary mt-2 inline-block transition-colors">
               Ver sesiones →
            </a>
        </div>

    </div>
</div>

<script>
function kpiStrip() {
    return {
        // Mirror Livewire public props — updated by $wire.$watch
        vals: {
            totalSalesToday:     {{ $totalSalesToday }},
            totalSalesMonth:     {{ $totalSalesMonth }},
            transactionCount:    {{ $transactionCount }},
            avgTicket:           {{ $avgTicket }},
            utilidadHoy:         {{ $utilidadHoy }},
            pendingClosings:     {{ $pendingClosings }},
            alertCount:          {{ $alertCount }},
            activeSessionsCount: {{ $activeSessionsCount }},
        },
        dirs: {
            totalSalesToday: null,
            totalSalesMonth: null,
            utilidadHoy:     null,
        },
        _timers: {},

        init() {
            const props = ['totalSalesToday', 'totalSalesMonth', 'transactionCount',
                           'avgTicket', 'utilidadHoy', 'pendingClosings',
                           'alertCount', 'activeSessionsCount'];

            props.forEach(prop => {
                this.$wire.$watch(prop, (newVal, oldVal) => {
                    const prev = this.vals[prop] ?? oldVal;
                    this.morphTo(prop, prev, newVal);
                });
            });
        },

        morphTo(prop, from, to) {
            from = Number(from) || 0;
            to   = Number(to)   || 0;
            if (from === to) return;

            // Direction signal (only for monetary props)
            if (['totalSalesToday', 'totalSalesMonth', 'utilidadHoy'].includes(prop)) {
                this.dirs[prop] = to > from ? 'up' : 'down';
                clearTimeout(this._timers[prop]);
                this._timers[prop] = setTimeout(() => this.dirs[prop] = null, 3000);
            }

            // Count-up animation
            const dur = 750, t0 = performance.now();
            const step = (t) => {
                const p = Math.min((t - t0) / dur, 1);
                const e = 1 - Math.pow(1 - p, 3); // cubic ease-out
                this.vals[prop] = from + (to - from) * e;
                if (p < 1) requestAnimationFrame(step);
                else this.vals[prop] = to;
            };
            requestAnimationFrame(step);
        },
    };
}
</script>
