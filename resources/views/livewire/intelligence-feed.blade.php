<div wire:poll.30s
     wire:loading.class.delay.long="opacity-60"
     class="transition-opacity duration-300">

{{-- ══════════════════════════════════════════════════════
     OPERATIONAL INTELLIGENCE PANEL
══════════════════════════════════════════════════════ --}}
<div class="bg-white border border-gray-200/60 rounded-2xl shadow-card overflow-hidden">

    {{-- ── Top accent bar ──────────────────────────────────────────────────── --}}
    <div class="h-[3px] w-full
        @if($overallHealth === 'critical') bg-gradient-to-r from-red-500 via-red-600 to-rose-500
        @elseif($overallHealth === 'warning') bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400
        @else bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700
        @endif">
    </div>

    {{-- ── Panel header ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            {{-- Live indicator --}}
            <div class="relative flex h-2 w-2 shrink-0">
                @if($criticalCount > 0)
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                @elseif($warningCount > 0)
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                @else
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                @endif
            </div>

            <div>
                <h2 class="text-[13px] font-bold text-gray-900 leading-none">Operational Intelligence</h2>
                <p class="text-[10px] text-gray-400 mt-0.5 font-medium tracking-wide uppercase">
                    Detección predictiva · Sistema activo
                </p>
            </div>

            {{-- Signal counts --}}
            <div class="flex items-center gap-1.5 ml-2">
                @if($criticalCount > 0)
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200/80">
                        <span class="w-1 h-1 rounded-full bg-red-500"></span>
                        {{ $criticalCount }} CRÍTICO{{ $criticalCount > 1 ? 'S' : '' }}
                    </span>
                @endif
                @if($warningCount > 0)
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200/80">
                        <span class="w-1 h-1 rounded-full bg-amber-400"></span>
                        {{ $warningCount }} ALERTA{{ $warningCount > 1 ? 'S' : '' }}
                    </span>
                @endif
                @if($noticeCount > 0)
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 border border-brand-100">
                        {{ $noticeCount }} aviso{{ $noticeCount > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
        </div>

        <span class="text-[10px] font-mono text-gray-300 tabular-nums">LIVE · 30s</span>
    </div>

    {{-- ── Body: Health Scores + Signal Feed ──────────────────────────────── --}}
    <div class="grid grid-cols-5 divide-x divide-gray-100 min-h-0">

        {{-- ── LEFT: Signal Feed (3 cols) ──────────────────────────────────── --}}
        <div class="col-span-3 overflow-y-auto" style="max-height:340px">
            @if($signals->isEmpty())
                <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-[13px] font-semibold text-gray-700">Operación limpia</p>
                    <p class="text-[11px] text-gray-400 mt-1">Sin señales activas · Todo en parámetros normales</p>
                </div>
            @else
                <div class="divide-y divide-gray-50/80">
                    @foreach($grouped as $severityValue => $group)
                        @php
                            $firstSignal = $group->first();
                            $sev = $firstSignal->severity;
                        @endphp

                        {{-- Severity section header --}}
                        <div class="flex items-center gap-2 px-5 py-2 bg-gray-50/60 sticky top-0 z-10">
                            <div class="relative flex h-1.5 w-1.5 shrink-0">
                                @if($sev->ringAnimated())
                                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full {{ $sev->dotClass() }} opacity-75"></span>
                                @endif
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full {{ $sev->dotClass() }}"></span>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-[0.14em] text-gray-400">
                                {{ $sev->label() }} — {{ $group->count() }} señal{{ $group->count() > 1 ? 'es' : '' }}
                            </span>
                        </div>

                        @foreach($group as $signal)
                            @php
                                $isDigest = $signal->isDigest;
                                $digest   = $isDigest ? $signal->digestSignals() : [];
                            @endphp

                            <div class="px-5 py-3.5 hover:bg-gray-50/60 transition-colors duration-150 group">
                                <div class="flex items-start gap-3">

                                    {{-- Type icon --}}
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5
                                        @if($signal->severity === \App\Enums\SignalSeverity::CRITICAL) bg-red-50
                                        @elseif($signal->severity === \App\Enums\SignalSeverity::WARNING) bg-amber-50
                                        @elseif($signal->severity === \App\Enums\SignalSeverity::NOTICE)  bg-brand-50
                                        @else bg-blue-50
                                        @endif">
                                        <svg class="w-3.5 h-3.5
                                            @if($signal->severity === \App\Enums\SignalSeverity::CRITICAL) text-red-500
                                            @elseif($signal->severity === \App\Enums\SignalSeverity::WARNING) text-amber-500
                                            @elseif($signal->severity === \App\Enums\SignalSeverity::NOTICE) text-brand-600
                                            @else text-blue-500
                                            @endif"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="{{ $signal->type->iconPath() }}"/>
                                        </svg>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        {{-- Title + type badge --}}
                                        <div class="flex items-start justify-between gap-2 mb-1">
                                            <p class="text-[12px] font-semibold text-gray-800 leading-snug">
                                                {{ $signal->title }}
                                                @if($signal->isPredictive)
                                                    <span class="inline-flex items-center ml-1 text-[8px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 border border-blue-100">
                                                        PREDICTIVO
                                                    </span>
                                                @endif
                                            </p>
                                            <span class="text-[9px] font-semibold shrink-0 text-gray-400 tabular-nums">
                                                {{ $signal->ageInMinutes() > 0 ? $signal->ageInMinutes() . 'm' : 'ahora' }}
                                            </span>
                                        </div>

                                        {{-- Body or digest bullets --}}
                                        @if($isDigest && !empty($digest))
                                            <div class="space-y-0.5 mt-1">
                                                @foreach($digest as $item)
                                                    <p class="text-[11px] text-gray-500 leading-snug">
                                                        · {{ $item['title'] }}
                                                    </p>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-[11px] text-gray-500 leading-relaxed">{{ $signal->body }}</p>
                                        @endif

                                        {{-- Footer row --}}
                                        <div class="flex items-center justify-between mt-2">
                                            <div class="flex items-center gap-2">
                                                {{-- Type pill --}}
                                                <span class="text-[9px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $signal->severity->badgeClasses() }}">
                                                    {{ $signal->typeLabel() }}
                                                </span>

                                                {{-- Confidence bar --}}
                                                <div class="flex items-center gap-1" title="Confianza: {{ $signal->confidencePct() }}%">
                                                    <div class="w-16 h-1 bg-gray-100 rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full transition-all
                                                            @if($signal->severity === \App\Enums\SignalSeverity::CRITICAL) bg-red-400
                                                            @elseif($signal->severity === \App\Enums\SignalSeverity::WARNING) bg-amber-400
                                                            @else bg-brand-400
                                                            @endif"
                                                             style="width:{{ $signal->confidencePct() }}%">
                                                        </div>
                                                    </div>
                                                    <span class="text-[9px] text-gray-300 tabular-nums">{{ $signal->confidencePct() }}%</span>
                                                </div>
                                            </div>

                                            {{-- Suggested action --}}
                                            @if($signal->suggestedAction)
                                                <span class="text-[10px] text-gray-400 italic truncate max-w-[140px]">
                                                    → {{ $signal->suggestedAction }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── RIGHT: Health Scores (2 cols) ──────────────────────────────── --}}
        <div class="col-span-2 flex flex-col">

            {{-- Header --}}
            <div class="px-5 py-3 border-b border-gray-100">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400">
                    Health Score · Sedes
                </p>
            </div>

            {{-- Sede scores --}}
            <div class="flex-1 divide-y divide-gray-50/80 overflow-y-auto" style="max-height:295px">
                @forelse($healthScores as $score)
                    @php
                        $status = $score['status'];
                        $total  = $score['total'];
                        $pct    = min(100, $total);
                        $barColor = match($status) {
                            'critical' => 'bg-red-500',
                            'warning'  => 'bg-amber-400',
                            default    => 'bg-emerald-500',
                        };
                        $textColor = match($status) {
                            'critical' => 'text-red-600',
                            'warning'  => 'text-amber-600',
                            default    => 'text-emerald-600',
                        };
                        $statusLabel = match($status) {
                            'critical' => 'CRÍTICO',
                            'warning'  => 'ALERTA',
                            default    => 'OK',
                        };
                    @endphp
                    <div class="px-5 py-3.5 hover:bg-gray-50/40 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[12px] font-semibold text-gray-800 truncate flex-1 mr-2">
                                {{ $score['sede_name'] }}
                            </p>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="text-[14px] font-bold {{ $textColor }} tabular-nums leading-none">
                                    {{ $total }}
                                </span>
                                <span class="text-[8px] font-bold uppercase tracking-wider {{ $textColor }} opacity-70">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>

                        {{-- Score bar --}}
                        <div class="h-[3px] bg-gray-100 rounded-full overflow-hidden mb-2">
                            <div class="h-full {{ $barColor }} rounded-full transition-all duration-500"
                                 style="width:{{ $pct }}%"></div>
                        </div>

                        {{-- Breakdown micro-labels --}}
                        <div class="grid grid-cols-4 gap-1">
                            @foreach(['cash' => 'Caja', 'approvals' => 'Aprob.', 'inventory' => 'Stock', 'operational' => 'Ops.'] as $key => $lbl)
                                @php
                                    $bd = $score['breakdown'][$key] ?? ['score' => 0, 'max' => 0];
                                    $p  = $bd['max'] > 0 ? round(($bd['score'] / $bd['max']) * 100) : 0;
                                    $dot = $p >= 80 ? 'bg-emerald-400' : ($p >= 50 ? 'bg-amber-400' : 'bg-red-400');
                                @endphp
                                <div class="flex flex-col items-center">
                                    <div class="w-[3px] h-4 bg-gray-100 rounded-full overflow-hidden mb-0.5">
                                        <div class="w-full {{ $dot }} rounded-full transition-all"
                                             style="height:{{ $p }}%"></div>
                                    </div>
                                    <span class="text-[8px] text-gray-400 leading-none">{{ $lbl }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Top issue if any --}}
                        @php
                            $allIssues = collect($score['breakdown'])->flatMap(fn ($b) => $b['issues'] ?? [])->first();
                        @endphp
                        @if($allIssues)
                            <p class="text-[10px] text-gray-400 mt-1.5 truncate">
                                <span class="font-medium text-gray-500">↳</span> {{ $allIssues }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-8 text-center">
                        <p class="text-[12px] text-gray-400">Sin sedes activas</p>
                    </div>
                @endforelse
            </div>

            {{-- Summary footer --}}
            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/40 shrink-0">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-gray-400">
                        {{ $totalCount }} señal{{ $totalCount !== 1 ? 'es' : '' }} activa{{ $totalCount !== 1 ? 's' : '' }}
                    </span>
                    <span class="text-[10px] text-gray-400 font-mono">
                        {{ $healthScores->count() }} sede{{ $healthScores->count() !== 1 ? 's' : '' }} monitoreada{{ $healthScores->count() !== 1 ? 's' : '' }}
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>

</div>
