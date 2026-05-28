<div wire:poll.30s.keep-alive
     wire:loading.class.delay.long="opacity-60"
     class="transition-opacity duration-300">

    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="relative flex h-1.5 w-1.5 shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-blue-400"></span>
                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-blue-500"></span>
            </span>
            <h2 class="section-label">Mapa Operacional · Sedes</h2>
        </div>
        <span class="text-[10px] font-mono text-gray-300 tabular-nums">{{ now()->format('H:i:s') }}</span>
    </div>

    <div class="grid grid-cols-2 gap-3">
        @foreach($nodes as $node)
        @php
            $health     = $node['health'];
            $isOffline  = $health === \App\Enums\OperationalStatus::LOCKED;
            $isCritical = $health === \App\Enums\OperationalStatus::CRITICAL;
            $isWarning  = $health === \App\Enums\OperationalStatus::WARNING;
            $isHealthy  = $health === \App\Enums\OperationalStatus::HEALTHY;

            $cardBorder = $isCritical ? 'border-red-200/70'
                        : ($isWarning ? 'border-amber-200/70'
                        : ($isHealthy ? 'border-gray-200/60' : 'border-gray-100'));

            $accentLine = $isCritical ? 'bg-gradient-to-r from-red-500 to-rose-500'
                        : ($isWarning ? 'bg-gradient-to-r from-amber-400 to-orange-400'
                        : ($isHealthy ? 'bg-gradient-to-r from-emerald-400 to-teal-400' : 'bg-gray-200'));

            $cardBg = $isCritical ? 'bg-red-50/20'
                    : ($isWarning  ? 'bg-amber-50/10' : 'bg-white');

            $statusLabel = $isCritical ? 'CRÍTICO'
                         : ($isWarning ? 'ALERTA'
                         : ($isHealthy ? 'ACTIVO' : 'OFFLINE'));

            $statusLabelClr = $isCritical ? 'text-red-600 bg-red-50 border-red-200/80'
                            : ($isWarning  ? 'text-amber-700 bg-amber-50 border-amber-200/80'
                            : ($isHealthy  ? 'text-emerald-700 bg-emerald-50 border-emerald-200/80'
                            :                'text-gray-400 bg-gray-50 border-gray-200'));

            $onlineOps = collect($node['operators'])->where('status', 'online');
            $idleOps   = collect($node['operators'])->where('status', 'idle');
        @endphp
        <div class="border {{ $cardBorder }} {{ $cardBg }} rounded-xl shadow-card overflow-hidden card-lift"
             wire:key="sede-node-{{ $node['id'] }}">

            <div class="h-[2px] {{ $accentLine }}"></div>

            <div class="p-4">
                {{-- Header: name + status badge --}}
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="min-w-0">
                        <h4 class="text-[13px] font-semibold text-gray-800 leading-tight truncate">{{ $node['nombre'] }}</h4>
                        @if($node['ciudad'])
                        <p class="text-[10px] text-gray-400 mt-0.5 truncate">{{ $node['ciudad'] }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0 mt-0.5">
                        @if($isCritical)
                        <span class="relative flex h-[6px] w-[6px] shrink-0">
                            <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400"></span>
                            <span class="relative inline-flex rounded-full h-[6px] w-[6px] bg-red-500"></span>
                        </span>
                        @elseif($isWarning)
                        <span class="relative flex h-[6px] w-[6px] shrink-0">
                            <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-amber-400"></span>
                            <span class="relative inline-flex rounded-full h-[6px] w-[6px] bg-amber-400"></span>
                        </span>
                        @elseif($isHealthy)
                        <span class="relative flex h-[6px] w-[6px] shrink-0">
                            <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                            <span class="relative inline-flex rounded-full h-[6px] w-[6px] bg-emerald-500"></span>
                        </span>
                        @else
                        <span class="w-[6px] h-[6px] rounded-full bg-gray-300 shrink-0"></span>
                        @endif
                        <span class="text-[8px] font-bold uppercase tracking-[0.1em] px-1.5 py-[2px] rounded-full border {{ $statusLabelClr }} leading-none">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                {{-- Stat grid 2×2 --}}
                <div class="grid grid-cols-2 gap-x-3 gap-y-2 mb-3">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-0.5">Cajas</p>
                        <p class="text-[16px] font-bold tabular-nums leading-none {{ $node['openSessions'] > 0 ? 'text-emerald-600' : 'text-gray-300' }}">
                            {{ $node['openSessions'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-0.5">Cierres</p>
                        <p class="text-[16px] font-bold tabular-nums leading-none {{ $node['pendingClosings'] > 0 ? 'text-amber-500' : 'text-gray-300' }}">
                            {{ $node['pendingClosings'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-0.5">Alertas</p>
                        <p class="text-[16px] font-bold tabular-nums leading-none {{ $node['stockAlerts'] > 0 ? 'text-red-500' : 'text-gray-300' }}">
                            {{ $node['stockAlerts'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-0.5">Online</p>
                        <p class="text-[16px] font-bold tabular-nums leading-none {{ $onlineOps->count() > 0 ? 'text-blue-500' : 'text-gray-300' }}">
                            {{ $onlineOps->count() }}
                        </p>
                    </div>
                </div>

                {{-- Operator presence tags --}}
                @if(collect($node['operators'])->isNotEmpty())
                <div class="flex flex-wrap gap-1">
                    @foreach(collect($node['operators'])->take(3) as $op)
                    <div class="flex items-center gap-1 bg-gray-100/80 rounded-md px-1.5 py-[3px] max-w-[90px]">
                        {{-- Presence dot --}}
                        @if($op['status'] === 'online')
                        <span class="relative flex h-[5px] w-[5px] shrink-0">
                            <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                            <span class="relative inline-flex rounded-full h-[5px] w-[5px] bg-emerald-500"></span>
                        </span>
                        @else
                        <span class="w-[5px] h-[5px] rounded-full bg-amber-300 shrink-0"></span>
                        @endif
                        <span class="text-[9px] font-medium text-gray-600 leading-none truncate">{{ $op['name'] }}</span>
                    </div>
                    @endforeach
                    @if(collect($node['operators'])->count() > 3)
                    <span class="text-[9px] text-gray-400 bg-gray-50 px-1.5 py-[3px] rounded-md leading-none">
                        +{{ collect($node['operators'])->count() - 3 }}
                    </span>
                    @endif
                </div>
                @elseif($isOffline)
                <p class="text-[10px] text-gray-300 italic">Sin sesiones activas</p>
                @endif

            </div>
        </div>
        @endforeach

        @if($nodes->isEmpty())
        <div class="col-span-2 bg-white border border-gray-200/60 rounded-xl p-8 text-center">
            <p class="text-[12px] text-gray-400">No hay sedes activas configuradas</p>
        </div>
        @endif
    </div>

</div>
