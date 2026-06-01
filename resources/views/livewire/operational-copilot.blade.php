<div wire:poll.60s
     wire:loading.class.delay.long="opacity-60"
     class="space-y-3 transition-opacity duration-300">

{{-- ══════════════════════════════════════════════════════
     INTELLIGENCE RIBBON — top-level system status strip
══════════════════════════════════════════════════════ --}}
@php
    $color = $digest['state_color'];
    $ribbonBg = match($color) {
        'red'     => 'bg-gradient-to-r from-red-600 via-red-700 to-rose-700',
        'amber'   => 'bg-gradient-to-r from-amber-500 via-amber-600 to-orange-500',
        'emerald' => 'bg-gradient-to-r from-emerald-600 via-emerald-700 to-teal-600',
        default   => 'bg-gradient-to-r from-brand-700 via-brand-800 to-brand-900',
    };
    $ribbonIcon = match($digest['state']) {
        'critical' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'degraded' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'optimal'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        default    => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    };
@endphp

<div class="{{ $ribbonBg }} rounded-xl px-5 py-3 flex items-center justify-between shadow-brand">

    {{-- Left: status + top concern --}}
    <div class="flex items-center gap-3 min-w-0">
        <div class="flex items-center gap-2 shrink-0">
            <div class="relative flex h-2 w-2 shrink-0">
                @if($digest['state'] === 'critical')
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-white/60"></span>
                @endif
                <span class="relative inline-flex rounded-full h-2 w-2 bg-white/90"></span>
            </div>
            <span class="text-[10px] font-bold tracking-[0.14em] uppercase text-white/80">
                Operational Copilot
            </span>
            <span class="inline-flex items-center text-[9px] font-bold tracking-wider px-2 py-0.5 rounded-full
                @if($digest['state'] === 'critical') bg-red-900/50 text-red-200 border border-red-700/50
                @elseif($digest['state'] === 'degraded') bg-amber-900/40 text-amber-200 border border-amber-700/50
                @elseif($digest['state'] === 'optimal') bg-emerald-900/40 text-emerald-200 border border-emerald-700/50
                @else bg-brand-900/50 text-blue-200 border border-brand-700/50
                @endif">
                {{ $digest['state_label'] }}
            </span>
        </div>

        @if($digest['top_concern'])
        <div class="hidden lg:flex items-center gap-2 min-w-0">
            <div class="w-px h-4 bg-white/15 shrink-0"></div>
            <svg class="w-3 h-3 text-white/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $ribbonIcon }}"/>
            </svg>
            <p class="text-[12px] text-white/80 truncate">
                <span class="font-semibold text-white">{{ $digest['top_concern'] }}</span>
                @if($digest['top_narrative'])
                    <span class="text-white/60"> · {{ Str::limit($digest['top_narrative'], 80) }}</span>
                @endif
            </p>
        </div>
        @endif
    </div>

    {{-- Right: quick stats --}}
    <div class="flex items-center gap-3 shrink-0 ml-4">
        <div class="hidden sm:flex items-center gap-3 text-[11px] text-white/60">
            @if($digest['critical_count'] > 0)
                <span class="text-red-300 font-semibold">{{ $digest['critical_count'] }} crítica{{ $digest['critical_count'] > 1 ? 's' : '' }}</span>
            @endif
            @if($digest['warning_count'] > 0)
                <span class="text-amber-300 font-semibold">{{ $digest['warning_count'] }} alerta{{ $digest['warning_count'] > 1 ? 's' : '' }}</span>
            @endif
            <span>Health: <span class="text-white font-bold">{{ $digest['avg_health'] }}/100</span></span>
            <span>{{ $digest['total_sedes'] }} sede{{ $digest['total_sedes'] !== 1 ? 's' : '' }}</span>
        </div>
        <span class="text-[9px] font-mono text-white/25 tabular-nums">60s</span>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     COPILOT PANEL — Narrative summaries + AI context
══════════════════════════════════════════════════════ --}}
<div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">

    {{-- ── Top accent ──────────────────────────────────────────────────────── --}}
    <div class="h-[3px] w-full
        @if($digest['state'] === 'critical') bg-gradient-to-r from-red-500 via-red-600 to-rose-500
        @elseif($digest['state'] === 'degraded') bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400
        @else bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700
        @endif">
    </div>

    <div class="grid grid-cols-5 divide-x divide-gray-100">

        {{-- ── Left: Narrative Summary Cards (3 cols) ─────────────────────── --}}
        <div class="col-span-3">

            {{-- Panel header --}}
            <div class="flex items-center justify-between px-6 py-3.5 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <span class="text-[11px] font-bold text-gray-700 uppercase tracking-[0.12em]">
                        Narrativas Ejecutivas
                    </span>
                </div>
                <span class="text-[10px] text-gray-300 font-mono">COPILOT · AI-READY</span>
            </div>

            {{-- Summary cards --}}
            <div class="divide-y divide-gray-50/80">
                @forelse($displaySummaries as $summary)
                    @php
                        $sev      = $summary->severity;
                        $trend    = $summary->trend;
                        $isPos    = $summary->isPositive();
                        $cardBg   = $isPos ? 'bg-emerald-50/30' : $sev->cardBgClass();
                        $border   = $isPos ? 'border-emerald-100/80' : $sev->cardBorderClass();
                    @endphp

                    <div class="px-6 py-4">
                        {{-- Card header --}}
                        <div class="flex items-start justify-between gap-3 mb-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                {{-- Trend indicator --}}
                                <span class="text-[14px] font-bold leading-none {{ $trend->colorClass() }} shrink-0"
                                      title="{{ $trend->label() }}">
                                    {{ $trend->arrow() }}
                                </span>

                                {{-- Title --}}
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-[13px] font-bold text-gray-900 leading-none">
                                            {{ $summary->title }}
                                        </p>
                                        @if($isPos)
                                            <span class="inline-flex items-center text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/60 shrink-0">
                                                SALUDABLE
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-[9px] font-bold px-2 py-0.5 rounded-full {{ $sev->badgeClasses() }} shrink-0">
                                                {{ $sev->label() }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        {{ $summary->type->label() }} · {{ $trend->label() }}
                                        @if(isset($summary->context['delta']) && $summary->context['delta'] !== 0)
                                            @php $delta = $summary->context['delta']; @endphp
                                            · <span class="{{ $delta > 0 ? 'text-emerald-500' : 'text-amber-500' }}">
                                                {{ $delta > 0 ? '+' : '' }}{{ $delta }}pts
                                              </span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- Health score if available --}}
                            @if(isset($summary->context['health_score']) && $summary->context['health_score'] !== null)
                                @php
                                    $hs = $summary->context['health_score'];
                                    $hsColor = $hs >= 80 ? 'text-emerald-600' : ($hs >= 60 ? 'text-amber-600' : 'text-red-600');
                                @endphp
                                <div class="text-right shrink-0">
                                    <p class="text-[18px] font-bold {{ $hsColor }} leading-none tabular-nums">{{ $hs }}</p>
                                    <p class="text-[8px] text-gray-400 uppercase tracking-wider">/100</p>
                                </div>
                            @endif
                        </div>

                        {{-- Narrative --}}
                        <p class="text-[12px] text-gray-700 leading-relaxed">{{ $summary->narrative }}</p>

                        {{-- Interpretation --}}
                        @if(!$isPos && $summary->interpretation)
                            <div class="mt-2 pl-3 border-l-2
                                @if($sev === \App\Enums\SignalSeverity::CRITICAL) border-red-300
                                @elseif($sev === \App\Enums\SignalSeverity::WARNING) border-amber-300
                                @else border-brand-200
                                @endif">
                                <p class="text-[11px] text-gray-500 italic leading-relaxed">
                                    {{ $summary->interpretation }}
                                </p>
                            </div>
                        @endif

                        {{-- Suggested action --}}
                        @if($summary->suggestedAction)
                            <div class="mt-2.5 flex items-center gap-1.5">
                                <svg class="w-3 h-3 shrink-0
                                    @if($sev === \App\Enums\SignalSeverity::CRITICAL) text-red-400
                                    @elseif($sev === \App\Enums\SignalSeverity::WARNING) text-amber-400
                                    @else text-brand-500
                                    @endif"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                          d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                <span class="text-[11px] font-semibold
                                    @if($sev === \App\Enums\SignalSeverity::CRITICAL) text-red-600
                                    @elseif($sev === \App\Enums\SignalSeverity::WARNING) text-amber-700
                                    @else text-brand-700
                                    @endif">
                                    {{ $summary->suggestedAction }}
                                </span>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-center px-6">
                        <div class="w-10 h-10 rounded-2xl bg-brand-50 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <p class="text-[12px] font-semibold text-gray-600">Sin narrativas activas</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">La operación no presenta patrones de riesgo detectables</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Right: System Overview + AI Context (2 cols) ────────────────── --}}
        <div class="col-span-2 flex flex-col">

            {{-- Panel header --}}
            <div class="px-5 py-3.5 border-b border-gray-100">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">
                    System Digest
                </p>
            </div>

            {{-- System state overview --}}
            <div class="px-5 py-4 border-b border-gray-50">
                <div class="flex items-center gap-2 mb-4">
                    <div class="relative flex h-2 w-2 shrink-0">
                        @if($digest['state'] === 'critical')
                            <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400"></span>
                        @endif
                        <span class="relative inline-flex h-2 w-2 rounded-full
                            @if($digest['state'] === 'critical') bg-red-500
                            @elseif($digest['state'] === 'degraded') bg-amber-400
                            @elseif($digest['state'] === 'optimal') bg-emerald-500
                            @else bg-brand-500
                            @endif">
                        </span>
                    </div>
                    <span class="text-[11px] font-bold
                        @if($digest['state'] === 'critical') text-red-700
                        @elseif($digest['state'] === 'degraded') text-amber-700
                        @elseif($digest['state'] === 'optimal') text-emerald-700
                        @else text-brand-700
                        @endif">
                        {{ $digest['state_label'] }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['label' => 'Señales', 'value' => $digest['signal_count'],   'sub' => $digest['critical_count'] . ' críticas'],
                        ['label' => 'Sedes',   'value' => $digest['total_sedes'],    'sub' => $digest['risky_sedes'] . ' en riesgo'],
                        ['label' => 'Health',  'value' => $digest['avg_health'],     'sub' => 'promedio'],
                        ['label' => 'Alertas', 'value' => $digest['warning_count'], 'sub' => 'en monitoreo'],
                    ] as $kpi)
                    <div class="rounded-xl bg-gray-50/70 px-3 py-2.5 border border-gray-100">
                        <p class="text-[16px] font-bold text-gray-900 leading-none tabular-nums">{{ $kpi['value'] }}</p>
                        <p class="text-[10px] font-semibold text-gray-500 mt-0.5 leading-none">{{ $kpi['label'] }}</p>
                        <p class="text-[9px] text-gray-400 mt-0.5">{{ $kpi['sub'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- All summaries quick list --}}
            @if($summaries->count() > $displaySummaries->count())
            <div class="px-5 py-3 border-b border-gray-50">
                <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-gray-300 mb-2">
                    Todas las sedes
                </p>
                <div class="space-y-1.5">
                    @foreach($summaries->take(5) as $s)
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] {{ $s->trend->colorClass() }} shrink-0">{{ $s->trend->arrow() }}</span>
                        <span class="text-[10px] text-gray-600 truncate flex-1">{{ $s->title }}</span>
                        @if(isset($s->context['health_score']))
                        <span class="text-[9px] font-mono font-bold shrink-0
                            {{ $s->context['health_score'] >= 80 ? 'text-emerald-500' : ($s->context['health_score'] >= 60 ? 'text-amber-500' : 'text-red-500') }}">
                            {{ $s->context['health_score'] }}
                        </span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- AI Context Layer --}}
            <div class="px-5 py-4 mt-auto">
                <div class="rounded-xl border border-brand-100/80 bg-brand-50/20 px-4 py-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-brand-600/10 flex items-center justify-center">
                                <svg class="w-2.5 h-2.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold text-brand-700 uppercase tracking-[0.1em]">
                                AI Context
                            </span>
                        </div>
                        <span class="text-[8px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-brand-100 text-brand-700 border border-brand-200/60">
                            AI READY
                        </span>
                    </div>

                    <p class="text-[10px] text-brand-600/70 leading-relaxed mb-3">
                        Contexto operacional estructurado — listo para integración con LLMs como Claude.
                    </p>

                    {{-- Context stats --}}
                    <div class="flex items-center gap-2 text-[9px] text-brand-500/70 mb-3">
                        <span>{{ $context['signals']['total'] }} señales</span>
                        <span>·</span>
                        <span>{{ count($context['sedes']) }} sedes</span>
                        <span>·</span>
                        <span>{{ count($context['top_concerns']) }} narrativas</span>
                    </div>

                    {{-- Toggle context button --}}
                    <button wire:click="toggleContext"
                            class="w-full text-[10px] font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200/60 rounded-lg py-1.5 transition-colors">
                        {{ $showContext ? 'Ocultar contexto' : 'Ver contexto estructurado →' }}
                    </button>
                </div>

                {{-- Context viewer --}}
                @if($showContext && $promptContext)
                <div class="mt-3 relative">
                    <pre class="text-[9px] font-mono leading-relaxed text-gray-600 bg-gray-50 border border-gray-200 rounded-xl p-3 overflow-auto"
                         style="max-height:160px">{{ $promptContext }}</pre>
                </div>
                @endif
            </div>

        </div>
    </div>
</div>

</div>
