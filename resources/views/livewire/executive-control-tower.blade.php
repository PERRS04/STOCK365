<div wire:poll.60s class="space-y-4">

{{-- ═══════════════════════════════════════════════════════════════════════
     EXECUTIVE HEADER — global health, state, trend
═══════════════════════════════════════════════════════════════════════ --}}
<div class="relative overflow-hidden rounded-2xl border
    @if($snap->globalHealthStatus === 'critical') border-red-200/70 bg-gradient-to-br from-red-950/95 via-red-900/90 to-slate-900
    @elseif($snap->globalHealthStatus === 'warning') border-amber-200/50 bg-gradient-to-br from-slate-900 via-amber-950/80 to-slate-900
    @else border-slate-700/60 bg-gradient-to-br from-[#001240] via-slate-900 to-[#001a3a]
    @endif shadow-2xl">

    {{-- Subtle grid texture --}}
    <div class="absolute inset-0 opacity-[0.03]"
         style="background-image: repeating-linear-gradient(0deg, transparent, transparent 24px, rgba(255,255,255,.5) 24px, rgba(255,255,255,.5) 25px), repeating-linear-gradient(90deg, transparent, transparent 24px, rgba(255,255,255,.5) 24px, rgba(255,255,255,.5) 25px)">
    </div>

    <div class="relative px-6 py-5">

        {{-- Top row: state label + actions --}}
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                {{-- Animated pulse --}}
                <span class="relative flex h-2.5 w-2.5 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-60
                        @if($snap->globalHealthStatus === 'critical') bg-red-400
                        @elseif($snap->globalHealthStatus === 'warning') bg-amber-400
                        @else bg-emerald-400 @endif"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5
                        @if($snap->globalHealthStatus === 'critical') bg-red-500
                        @elseif($snap->globalHealthStatus === 'warning') bg-amber-400
                        @else bg-emerald-500 @endif"></span>
                </span>

                <span class="text-[10px] font-bold tracking-[0.2em] uppercase
                    @if($snap->globalHealthStatus === 'critical') text-red-400
                    @elseif($snap->globalHealthStatus === 'warning') text-amber-400
                    @else text-emerald-400 @endif">
                    {{ $snap->systemLabel }} · EXECUTIVE CONTROL TOWER
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[10px] text-slate-500">
                    {{ $snap->generatedAt->format('H:i') }}
                </span>
                <button wire:click="refresh"
                        class="text-[10px] text-slate-500 hover:text-white transition-colors px-2 py-1 rounded hover:bg-white/10"
                        title="Forzar actualización">
                    ↺
                </button>
                <button wire:click="toggleExpanded"
                        class="text-[10px] text-slate-500 hover:text-white transition-colors px-2 py-1 rounded hover:bg-white/10">
                    @if($expanded) ▲ compactar @else ▼ expandir @endif
                </button>
            </div>
        </div>

        {{-- ── Core KPI Row ──────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">

            {{-- Global Health Score --}}
            <div class="lg:col-span-1">
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">Salud Global</p>
                <div class="flex items-end gap-2">
                    <span class="text-[42px] font-black leading-none tabular-nums
                        @if($snap->globalHealthStatus === 'critical') text-red-400
                        @elseif($snap->globalHealthStatus === 'warning') text-amber-400
                        @else text-white @endif">
                        {{ $snap->globalHealthScore }}
                    </span>
                    <div class="pb-1.5">
                        <span class="text-[11px] text-slate-400">/100</span>
                        <div class="flex items-center gap-1 mt-0.5">
                            @if($snap->globalHealthDelta >= 0)
                                <span class="text-[11px] font-bold text-emerald-400">+{{ $snap->globalHealthDelta }}</span>
                                <span class="text-[9px] text-slate-500">↑</span>
                            @else
                                <span class="text-[11px] font-bold text-red-400">{{ $snap->globalHealthDelta }}</span>
                                <span class="text-[9px] text-slate-500">↓</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-2 h-1 rounded-full bg-slate-700 overflow-hidden">
                    <div class="h-full rounded-full transition-all
                        @if($snap->globalHealthStatus === 'critical') bg-red-500
                        @elseif($snap->globalHealthStatus === 'warning') bg-amber-400
                        @else bg-emerald-500 @endif"
                         style="width: {{ $snap->globalHealthScore }}%"></div>
                </div>
            </div>

            {{-- Sede Distribution --}}
            <div class="lg:col-span-1">
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">Sedes</p>
                <div class="space-y-1.5 mt-1">
                    @if($snap->criticalSedesCount > 0)
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0 animate-pulse"></span>
                        <span class="text-[11px] text-red-400 font-semibold">{{ $snap->criticalSedesCount }} crítica{{ $snap->criticalSedesCount !== 1 ? 's' : '' }}</span>
                    </div>
                    @endif
                    @if($snap->warningSedesCount > 0)
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                        <span class="text-[11px] text-amber-400 font-semibold">{{ $snap->warningSedesCount }} alerta</span>
                    </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                        <span class="text-[11px] text-emerald-400 font-semibold">{{ $snap->healthySedesCount }} saludable{{ $snap->healthySedesCount !== 1 ? 's' : '' }}</span>
                    </div>
                </div>
            </div>

            {{-- Sales Today --}}
            <div class="lg:col-span-1">
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">Ventas Hoy</p>
                <p class="text-[26px] font-black text-white tabular-nums leading-none">
                    ${{ number_format($snap->salesTodayTotal, 0) }}
                </p>
                <p class="text-[10px] text-slate-500 mt-1">{{ number_format($snap->salesTodayCount) }} transacciones</p>
            </div>

            {{-- Sales Month --}}
            <div class="lg:col-span-1">
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">Mes Actual</p>
                <p class="text-[26px] font-black text-[#FFD100] tabular-nums leading-none">
                    ${{ number_format($snap->salesMonthTotal, 0) }}
                </p>
                <p class="text-[10px] text-slate-500 mt-1">acumulado del mes</p>
            </div>

            {{-- Operational Pressure --}}
            <div class="lg:col-span-1">
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">Presión Operativa</p>
                <p class="text-[26px] font-black tabular-nums leading-none
                    @if($snap->pendingApprovalsTotal >= 10) text-red-400
                    @elseif($snap->pendingApprovalsTotal >= 5) text-amber-400
                    @else text-slate-300 @endif">
                    {{ $snap->pendingApprovalsTotal }}
                </p>
                <p class="text-[10px] text-slate-500 mt-1">aprobaciones pend.</p>
                @if($snap->inventoryAlertCount > 0)
                <p class="text-[10px] text-red-400 mt-0.5">+ {{ $snap->inventoryAlertCount }} alertas inventario</p>
                @endif
            </div>

            {{-- Workforce --}}
            <div class="lg:col-span-1">
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">Equipo</p>
                <p class="text-[26px] font-black text-white tabular-nums leading-none">{{ $snap->workforceAvgScore }}</p>
                <p class="text-[10px] text-slate-500 mt-1">score promedio</p>
                @if($snap->coachingNeedsCount > 0)
                <p class="text-[10px] text-amber-400 mt-0.5">{{ $snap->coachingNeedsCount }} necesitan coaching</p>
                @endif
            </div>

        </div>

        {{-- ── Executive Narrative ───────────────────────────────────────────── --}}
        @if(count($snap->executiveSentences) > 0)
        <div class="mt-5 pt-4 border-t border-white/10">
            <p class="text-[10px] text-slate-500 uppercase tracking-widest mb-2">Resumen Ejecutivo</p>
            <div class="space-y-1">
                @foreach($snap->executiveSentences as $i => $sentence)
                <p class="text-[12px] {{ $i === 0 ? 'text-white font-semibold' : 'text-slate-400' }} leading-relaxed">
                    {{ $sentence }}
                </p>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     EXPANDED PANEL — priority + workforce + sede map
═══════════════════════════════════════════════════════════════════════ --}}
@if($expanded)
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- ── TOP RISKS ─────────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <h3 class="text-[13px] font-semibold text-gray-800">Riesgos Prioritarios</h3>
            @if($snap->criticalSedesCount > 0)
            <span class="text-[10px] font-bold text-red-600 bg-red-50 border border-red-200/80 px-2 py-0.5 rounded-full">
                {{ $snap->criticalSedesCount }} crítico{{ $snap->criticalSedesCount !== 1 ? 's' : '' }}
            </span>
            @else
            <span class="text-[10px] text-emerald-600 font-medium">Sin críticos</span>
            @endif
        </div>

        <div class="divide-y divide-gray-50">
            @forelse($snap->topRisks as $i => $risk)
            <div class="px-5 py-3.5">
                <div class="flex items-start gap-3">
                    <span class="shrink-0 text-[10px] font-black tabular-nums w-4 text-center mt-0.5
                        @if($risk['severity'] >= 4) text-red-500
                        @elseif($risk['severity'] >= 3) text-amber-500
                        @else text-blue-400 @endif">
                        {{ $i + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[11px] font-bold text-gray-800">{{ $risk['sedeName'] }}</span>
                            <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full
                                @if($risk['severity'] >= 4) bg-red-50 text-red-600 border border-red-200/60
                                @elseif($risk['severity'] >= 3) bg-amber-50 text-amber-600 border border-amber-200/60
                                @else bg-blue-50 text-blue-600 border border-blue-200/60 @endif">
                                {{ $risk['typeLabel'] }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 leading-relaxed line-clamp-2">{{ $risk['message'] }}</p>
                        @if(!empty($risk['action']))
                        <p class="text-[10px] font-medium text-stock-primary mt-1.5">→ {{ $risk['action'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center">
                <p class="text-[13px] font-semibold text-emerald-600">Sin riesgos activos</p>
                <p class="text-[11px] text-gray-400 mt-1">Todas las sedes operan normalmente</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── OPPORTUNITIES + WORKFORCE ───────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- Top Opportunities --}}
        <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-[13px] font-semibold text-gray-800">Oportunidades</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($snap->topOpportunities as $i => $opp)
                <div class="px-5 py-3">
                    <div class="flex items-start gap-2.5">
                        <span class="shrink-0 text-emerald-500 text-[11px] font-black mt-0.5">{{ $i + 1 }}</span>
                        <div class="min-w-0">
                            <p class="text-[12px] font-semibold text-gray-800">{{ $opp['sedeName'] }}</p>
                            <p class="text-[11px] text-gray-500 leading-relaxed line-clamp-2">{{ $opp['message'] }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-5 py-6 text-center">
                    <p class="text-[11px] text-gray-400">Sin oportunidades destacadas</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Workforce Executive Highlights --}}
        <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-[13px] font-semibold text-gray-800">Equipo de la Semana</h3>
            </div>
            <div class="px-5 py-4 space-y-3">

                @if($snap->topPerformer)
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest">Top Operador</p>
                        <p class="text-[13px] font-bold text-gray-800">{{ $snap->topPerformer['user_name'] }}</p>
                        <p class="text-[10px] text-gray-400">{{ $snap->topPerformer['label'] ?? '' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[24px] font-black tabular-nums text-[#003594]">{{ $snap->topPerformer['score'] }}</span>
                        <span class="text-[10px] text-gray-400">/100</span>
                    </div>
                </div>
                @endif

                @if($snap->mostImprovedOperator)
                <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest">Mejor Mejora</p>
                        <p class="text-[13px] font-bold text-gray-800">{{ $snap->mostImprovedOperator['user_name'] }}</p>
                    </div>
                    <span class="text-[18px] font-black text-emerald-500 tabular-nums">
                        +{{ $snap->mostImprovedOperator['delta'] }}
                    </span>
                </div>
                @endif

                @if($snap->mostConsistentOperator)
                <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest">Más Consistente</p>
                        <p class="text-[13px] font-bold text-gray-800">{{ $snap->mostConsistentOperator['user_name'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[13px] font-bold text-gray-700 tabular-nums">{{ $snap->mostConsistentOperator['avg_score'] }}</p>
                        <p class="text-[10px] text-gray-400">σ {{ $snap->mostConsistentOperator['stddev'] }}</p>
                    </div>
                </div>
                @endif

                @if(!$snap->topPerformer && !$snap->mostImprovedOperator)
                <p class="text-[12px] text-gray-400 text-center py-2">Sin datos de equipo esta semana</p>
                @endif

            </div>
        </div>

    </div>

    {{-- ── SEDE HEALTH MAP ─────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <h3 class="text-[13px] font-semibold text-gray-800">Mapa de Sedes</h3>
            <span class="text-[10px] text-gray-400">{{ $snap->totalSedesCount }} activas</span>
        </div>

        <div class="divide-y divide-gray-50 overflow-y-auto max-h-[420px]">
            @forelse(collect($snap->sedeMap)->sortBy('health') as $sedeId => $sede)
            @php
                $statusColor = match($sede['status']) {
                    'critical' => 'bg-red-500',
                    'warning'  => 'bg-amber-400',
                    default    => 'bg-emerald-500',
                };
                $textColor = match($sede['status']) {
                    'critical' => 'text-red-600',
                    'warning'  => 'text-amber-600',
                    default    => 'text-emerald-600',
                };
                $trendArrow = match($sede['trend']) {
                    'improving' => '↑',
                    'declining' => '↓',
                    default     => '→',
                };
                $trendColor = match($sede['trend']) {
                    'improving' => 'text-emerald-500',
                    'declining' => 'text-red-400',
                    default     => 'text-gray-400',
                };
            @endphp
            <div class="px-5 py-3 hover:bg-gray-50/50 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full {{ $statusColor }} shrink-0 {{ $sede['status'] === 'critical' ? 'animate-pulse' : '' }}"></span>
                        <span class="text-[12px] font-semibold text-gray-800 truncate">{{ $sede['sede_name'] }}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-2">
                        <span class="{{ $trendColor }} text-[11px]">{{ $trendArrow }}</span>
                        <span class="text-[13px] font-black tabular-nums {{ $textColor }}">{{ $sede['health'] }}</span>
                    </div>
                </div>

                {{-- Score bar --}}
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all {{ $statusColor }}"
                             style="width: {{ $sede['health'] }}%"></div>
                    </div>
                    <span class="text-[9px] tabular-nums text-gray-400 w-6 text-right shrink-0">
                        @if($sede['delta'] >= 0)+{{ $sede['delta'] }}@else{{ $sede['delta'] }}@endif
                    </span>
                </div>

                {{-- Top issue --}}
                @if(!empty($sede['top_issue']))
                <p class="text-[10px] text-gray-400 mt-1.5 truncate">↳ {{ $sede['top_issue'] }}</p>
                @endif

                {{-- 4-factor mini breakdown --}}
                @if($expanded && !empty($sede['breakdown']))
                <div class="grid grid-cols-4 gap-1 mt-2">
                    @foreach(['cash' => 'Caja', 'approvals' => 'Apr.', 'inventory' => 'Inv.', 'operational' => 'Op.'] as $key => $label)
                    @php $val = $sede['breakdown'][$key] ?? 0; @endphp
                    <div class="text-center">
                        <p class="text-[9px] text-gray-400">{{ $label }}</p>
                        <p class="text-[10px] font-bold tabular-nums {{ $val >= 75 ? 'text-emerald-600' : ($val >= 50 ? 'text-amber-600' : 'text-red-500') }}">{{ $val }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @empty
            <div class="px-5 py-8 text-center">
                <p class="text-[12px] text-gray-400">Sin sedes activas</p>
            </div>
            @endforelse
        </div>
    </div>

</div>
@endif

</div>
