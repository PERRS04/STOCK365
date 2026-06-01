<div class="p-6 space-y-6" wire:poll.60s>

    {{-- ── Filters ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3">

        {{-- Sede selector --}}
        <div class="flex items-center gap-2">
            <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Sede</span>
            <select wire:model.live="sedeFilter"
                    class="text-[13px] font-semibold text-[#001240] bg-white border border-gray-200 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-[#003594]/20 focus:border-[#003594] outline-none cursor-pointer">
                @foreach($this->sedes as $sede)
                    <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-px h-5 bg-gray-200"></div>

        {{-- Period selector --}}
        <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl p-1">
            <button wire:click="$set('periodType', 'weekly')"
                    class="px-3 py-1 rounded-lg text-[12px] font-semibold transition-all duration-150
                           {{ $periodType === 'weekly' ? 'bg-[#003594] text-white shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
                Esta semana
            </button>
            <button wire:click="$set('periodType', 'monthly')"
                    class="px-3 py-1 rounded-lg text-[12px] font-semibold transition-all duration-150
                           {{ $periodType === 'monthly' ? 'bg-[#003594] text-white shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
                Este mes
            </button>
        </div>

        <div class="ml-auto text-[11px] text-gray-300 font-medium tabular-nums">
            Actualiza cada 60s
        </div>
    </div>

    {{-- ── Team Health KPIs ─────────────────────────────────────────────────── --}}
    @php
        $health = $this->teamHealth;
        $dist   = $health['distribution'] ?? [];
        $avgBrk = $health['avg_breakdown'] ?? [];
    @endphp

    @if(($health['count'] ?? 0) > 0)
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Avg Score --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Puntaje promedio</div>
            <div class="text-[36px] font-black leading-none tabular-nums
                {{ $health['avg'] >= 70 ? 'text-emerald-500' : ($health['avg'] >= 55 ? 'text-[#003594]' : 'text-amber-500') }}">
                {{ $health['avg'] }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1">de 100 puntos</div>
        </div>

        {{-- Distribution --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3">Distribución</div>
            <div class="space-y-1.5">
                @if(($dist['excellence'] ?? 0) > 0)
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-emerald-600 font-semibold">Excelencia</span>
                    <span class="text-[13px] font-black text-emerald-600">{{ $dist['excellence'] }}</span>
                </div>
                @endif
                @if(($dist['strong'] ?? 0) > 0)
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-blue-600 font-semibold">Sólido Alto</span>
                    <span class="text-[13px] font-black text-blue-600">{{ $dist['strong'] }}</span>
                </div>
                @endif
                @if(($dist['attention'] ?? 0) > 0)
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-amber-600 font-semibold">Atención</span>
                    <span class="text-[13px] font-black text-amber-600">{{ $dist['attention'] }}</span>
                </div>
                @endif
                @if(($dist['coaching'] ?? 0) > 0)
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-red-500 font-semibold">Coaching</span>
                    <span class="text-[13px] font-black text-red-500">{{ $dist['coaching'] }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Factor averages --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm col-span-2">
            <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3">Factores del equipo</div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-2.5">
                @foreach([
                    ['Productividad', $avgBrk['productivity'] ?? 0, '25%'],
                    ['Calidad',       $avgBrk['quality']      ?? 0, '30%'],
                    ['Disciplina',    $avgBrk['discipline']   ?? 0, '25%'],
                    ['Consistencia',  $avgBrk['consistency']  ?? 0, '20%'],
                ] as [$label, $val, $weight])
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[11px] text-gray-500 font-medium">{{ $label }}</span>
                        <span class="text-[11px] font-black
                            {{ $val >= 70 ? 'text-emerald-600' : ($val >= 50 ? 'text-amber-600' : 'text-red-500') }}">
                            {{ $val }}
                        </span>
                    </div>
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500
                            {{ $val >= 70 ? 'bg-emerald-400' : ($val >= 50 ? 'bg-amber-400' : 'bg-red-400') }}"
                             style="width: {{ $val }}%"></div>
                    </div>
                    <div class="text-[9px] text-gray-300 mt-0.5 font-medium">Peso {{ $weight }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ── Main Grid ────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Leaderboard --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h2 class="text-[14px] font-black text-[#001240]">Rendimiento del equipo</h2>
                    <p class="text-[11px] text-gray-400 mt-0.5">Puntuación multifactor — productividad, calidad, disciplina, consistencia</p>
                </div>
                <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">{{ $health['count'] ?? 0 }} operadores</span>
            </div>

            @if($this->leaderboard->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-[13px] text-gray-400">Sin datos para este período.</p>
                <p class="text-[11px] text-gray-300 mt-1">Ejecuta <code class="text-[10px] bg-gray-100 px-1.5 py-0.5 rounded">php artisan workforce:compute</code> para generar scores.</p>
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($this->leaderboard as $i => $score)
                @php
                    $rankColor = match($i) { 0 => 'text-amber-500', 1 => 'text-gray-400', 2 => 'text-orange-400', default => 'text-gray-300' };
                    $barColor  = match(true) {
                        $score->total_score >= 85 => 'bg-emerald-400',
                        $score->total_score >= 70 => 'bg-[#003594]',
                        $score->total_score >= 55 => 'bg-indigo-400',
                        $score->total_score >= 40 => 'bg-amber-400',
                        default                   => 'bg-red-400',
                    };
                    $labelColor = match(true) {
                        $score->total_score >= 85 => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        $score->total_score >= 70 => 'bg-blue-50 text-blue-700 border-blue-200',
                        $score->total_score >= 55 => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                        $score->total_score >= 40 => 'bg-amber-50 text-amber-700 border-amber-200',
                        default                   => 'bg-red-50 text-red-600 border-red-200',
                    };
                @endphp
                <div class="px-5 py-3.5 hover:bg-gray-50/50 transition-colors">
                    <div class="flex items-center gap-4">
                        {{-- Rank --}}
                        <span class="text-[15px] font-black {{ $rankColor }} w-6 text-center shrink-0 tabular-nums">
                            {{ $i + 1 }}
                        </span>

                        {{-- Avatar --}}
                        <div class="w-8 h-8 rounded-full bg-[#003594]/10 border border-[#003594]/12 flex items-center justify-center shrink-0">
                            <span class="text-[11px] font-black text-[#003594]">{{ strtoupper(substr($score->user?->name ?? '?', 0, 1)) }}</span>
                        </div>

                        {{-- Name + bar --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="text-[13px] font-bold text-[#001240] truncate">{{ $score->user?->name ?? '—' }}</span>
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border {{ $labelColor }}">
                                    {{ __($score->performance_label) }}
                                </span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $barColor }} rounded-full transition-all duration-700"
                                     style="width: {{ $score->total_score }}%"></div>
                            </div>
                        </div>

                        {{-- Score --}}
                        <div class="text-right shrink-0">
                            <div class="text-[22px] font-black text-[#001240] tabular-nums leading-none">{{ $score->total_score }}</div>
                            <div class="text-[9px] text-gray-300 font-medium mt-0.5">/100</div>
                        </div>
                    </div>

                    {{-- Factor breakdown --}}
                    <div class="mt-2 ml-10 flex items-center gap-4 text-[10px] text-gray-400">
                        <span title="Productividad (25%)">
                            <span class="text-gray-300">Prod</span>
                            <span class="font-bold text-gray-500 ml-1">{{ $score->productivity_score }}</span>
                        </span>
                        <span title="Calidad (30%)">
                            <span class="text-gray-300">Cal</span>
                            <span class="font-bold text-gray-500 ml-1">{{ $score->quality_score }}</span>
                        </span>
                        <span title="Disciplina (25%)">
                            <span class="text-gray-300">Disc</span>
                            <span class="font-bold text-gray-500 ml-1">{{ $score->discipline_score }}</span>
                        </span>
                        <span title="Consistencia (20%)">
                            <span class="text-gray-300">Cons</span>
                            <span class="font-bold text-gray-500 ml-1">{{ $score->consistency_score }}</span>
                        </span>
                        @if($score->total_sales > 0)
                        <span class="ml-auto text-gray-300">{{ $score->total_sales }} ventas</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Right column --}}
        <div class="space-y-5">

            {{-- Week-over-week improvement --}}
            @if($this->teamImprovement->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h3 class="text-[13px] font-black text-[#001240]">Mejora semanal</h3>
                    <p class="text-[10px] text-gray-400 mt-0.5">Variación vs. semana anterior</p>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($this->teamImprovement->take(6) as $row)
                    @php
                        $delta = $row['delta'];
                        $deltaColor = match(true) {
                            $delta === null        => 'text-gray-300',
                            $delta > 0             => 'text-emerald-500',
                            $delta < 0             => 'text-red-400',
                            default                => 'text-gray-400',
                        };
                        $prefix = $delta > 0 ? '+' : '';
                    @endphp
                    <div class="px-5 py-2.5 flex items-center justify-between">
                        <span class="text-[12px] font-semibold text-gray-600 truncate max-w-[130px]">{{ $row['user_name'] }}</span>
                        <div class="flex items-center gap-3">
                            <span class="text-[13px] font-black text-[#001240] tabular-nums">{{ $row['score'] }}</span>
                            @if($delta !== null)
                            <span class="text-[11px] font-bold {{ $deltaColor }} tabular-nums w-10 text-right">
                                {{ $prefix }}{{ $delta }}
                            </span>
                            @else
                            <span class="text-[11px] text-gray-200 w-10 text-right">—</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Coaching opportunities --}}
            @if($this->coachingOpportunities->isNotEmpty())
            <div class="bg-amber-50 rounded-2xl border border-amber-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-amber-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <h3 class="text-[13px] font-black text-amber-800">Oportunidades coaching</h3>
                    </div>
                    <p class="text-[10px] text-amber-600 mt-1">Operadores bajo 55 pts — revisión recomendada</p>
                </div>
                <div class="divide-y divide-amber-100">
                    @foreach($this->coachingOpportunities as $score)
                    <div class="px-5 py-2.5 flex items-center justify-between">
                        <span class="text-[12px] font-semibold text-amber-800 truncate max-w-[140px]">{{ $score->user?->name ?? '—' }}</span>
                        <span class="text-[15px] font-black text-amber-600 tabular-nums">{{ $score->total_score }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Most consistent --}}
            @if($this->mostConsistent->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h3 class="text-[13px] font-black text-[#001240]">Más consistentes</h3>
                    <p class="text-[10px] text-gray-400 mt-0.5">Menor variación en 4 semanas</p>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($this->mostConsistent->take(5) as $row)
                    <div class="px-5 py-2.5 flex items-center justify-between">
                        <span class="text-[12px] font-semibold text-gray-600 truncate max-w-[140px]">{{ $row->user?->name ?? '—' }}</span>
                        <div class="text-right">
                            <div class="text-[13px] font-black text-[#001240] tabular-nums">{{ round($row->avg_score, 0) }}</div>
                            <div class="text-[9px] text-gray-300 tabular-nums">±{{ round($row->stddev_score ?? 0, 1) }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ── Empty state --}}
    @if(($health['count'] ?? 0) === 0 && $this->leaderboard->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-[#003594]/8 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-[#003594]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h3 class="text-[16px] font-black text-[#001240] mb-2">Sin datos de rendimiento aún</h3>
        <p class="text-[13px] text-gray-400 max-w-sm mx-auto">Los scores se calculan automáticamente cada lunes a las 06:00. También puedes generarlos manualmente:</p>
        <code class="inline-block mt-3 text-[11px] bg-gray-100 text-gray-600 px-4 py-2 rounded-xl font-mono">
            php artisan workforce:compute --period=weekly
        </code>
    </div>
    @endif

</div>
