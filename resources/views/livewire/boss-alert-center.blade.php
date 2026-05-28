<div wire:poll.25s.keep-alive
     wire:loading.class.delay.long="opacity-60"
     class="bg-white border border-gray-200/60 rounded-2xl shadow-card flex flex-col overflow-hidden transition-opacity duration-300 relative"
     x-data="{ tab: 'alerts' }">

    {{-- ── Force-close confirmation modal ─────────────────────────────────── --}}
    @if($forceCloseSessionId)
    <div class="absolute inset-0 z-20 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm rounded-2xl animate-feed-in">
        <div class="bg-white rounded-2xl shadow-card-xl border border-gray-200/80 w-full max-w-sm overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-red-500 to-rose-500"></div>
            <div class="px-6 py-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-[14px] font-bold text-gray-900">Confirmar cierre forzado</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Esta acción quedará registrada en auditoría</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[11px] font-bold uppercase tracking-[0.12em] text-gray-400">
                        Motivo <span class="text-red-400">*</span>
                    </label>
                    <textarea wire:model="forceCloseMotivo" rows="3"
                        placeholder="Describe el motivo del cierre forzado…"
                        class="w-full text-[13px] text-gray-800 bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-red-400/30 focus:border-red-300 placeholder:text-gray-300 transition-all"></textarea>
                    @error('forceCloseMotivo')
                    <p class="text-[11px] text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-2 mt-4">
                    <button wire:click="executeForceClose" wire:loading.attr="disabled"
                            class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-[13px] border border-red-700 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500/30">
                        <span wire:loading.remove wire:target="executeForceClose">Confirmar cierre</span>
                        <span wire:loading wire:target="executeForceClose" class="text-[12px]">Ejecutando…</span>
                    </button>
                    <button wire:click="cancelForceClose"
                            class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-medium text-[13px] border border-gray-200 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Header + Tab Bar ────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 shrink-0 gap-3">

        {{-- Status dot + title --}}
        <div class="flex items-center gap-2 shrink-0">
            @if($critical->isNotEmpty())
            <span class="relative flex h-1.5 w-1.5 shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-red-400"></span>
                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-500"></span>
            </span>
            @elseif($warning->isNotEmpty())
            <span class="relative flex h-1.5 w-1.5 shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-amber-400"></span>
                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-amber-400"></span>
            </span>
            @else
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
            @endif
            <h3 class="text-[13px] font-semibold text-gray-800">Command Feed</h3>
        </div>

        {{-- Tabs --}}
        <div class="flex items-center bg-gray-100/80 rounded-lg p-0.5 gap-0.5">
            <button @click="tab = 'alerts'"
                    class="text-[10px] font-bold px-2.5 py-1 rounded-md transition-all leading-none"
                    :class="tab === 'alerts'
                        ? 'bg-white text-gray-800 shadow-sm'
                        : 'text-gray-400 hover:text-gray-600'">
                Alertas
                @if($alerts->isNotEmpty())
                <span class="ml-1 text-[8px] font-bold px-1 py-[1px] rounded-full inline-block
                    {{ $critical->isNotEmpty() ? 'bg-red-500 text-white' : 'bg-amber-400 text-white' }}">
                    {{ $alerts->count() }}
                </span>
                @endif
            </button>
            <button @click="tab = 'feed'"
                    class="text-[10px] font-bold px-2.5 py-1 rounded-md transition-all leading-none"
                    :class="tab === 'feed'
                        ? 'bg-white text-gray-800 shadow-sm'
                        : 'text-gray-400 hover:text-gray-600'">
                Feed
            </button>
        </div>

        <span class="text-[10px] font-mono text-gray-300 tabular-nums shrink-0">{{ now()->format('H:i') }}</span>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAB 1 — ALERT FEED
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'alerts'" class="overflow-y-auto flex-1 divide-y divide-gray-50">

        @if($alerts->isEmpty())
        <div class="flex flex-col items-center justify-center px-5 py-10 gap-3">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                <svg class="w-5.5 h-5.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-center">
                <p class="text-[13px] font-semibold text-gray-700">Sistema operacional saludable</p>
                <p class="text-[11px] text-gray-400 mt-0.5">No hay alertas activas en ninguna sede</p>
            </div>
        </div>
        @else

        @if($critical->isNotEmpty())
        <div class="px-4 py-2 bg-red-50/60 border-b border-red-100/60">
            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-red-400 flex items-center gap-1.5">
                <span class="w-1 h-1 rounded-full bg-red-500"></span>
                Crítico — acción inmediata
            </p>
        </div>
        @foreach($critical as $alert)
        @include('livewire.boss-alert-center-row', ['alert' => $alert])
        @endforeach
        @endif

        @if($warning->isNotEmpty())
        <div class="px-4 py-2 bg-amber-50/60 border-b border-amber-100/60 {{ $critical->isNotEmpty() ? 'border-t border-amber-100/40' : '' }}">
            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-amber-500 flex items-center gap-1.5">
                <span class="w-1 h-1 rounded-full bg-amber-400"></span>
                Advertencia — revisar
            </p>
        </div>
        @foreach($warning as $alert)
        @include('livewire.boss-alert-center-row', ['alert' => $alert])
        @endforeach
        @endif

        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAB 2 — LIVE EVENT FEED (from OperationalRealtimeService)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'feed'" class="overflow-y-auto flex-1 divide-y divide-gray-50">
        @forelse($events as $event)
        <div class="flex items-stretch hover:bg-gray-50/60 transition-colors animate-feed-in" wire:key="ev-{{ $event->id }}">

            {{-- Priority bar --}}
            <div class="w-[3px] shrink-0 {{ $event->priorityBarClr() }}"></div>

            {{-- Content --}}
            <div class="flex items-start gap-3 px-4 py-3 flex-1 min-w-0">
                {{-- Icon --}}
                <div class="w-7 h-7 rounded-lg {{ $event->iconBg() }} {{ $event->iconClr() }} flex items-center justify-center shrink-0 mt-[1px]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $event->iconPath() }}"/>
                    </svg>
                </div>

                {{-- Text --}}
                <div class="flex-1 min-w-0">
                    <p class="text-[12px] font-semibold text-gray-800 leading-tight">{{ $event->title }}</p>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-snug truncate">{{ $event->description }}</p>
                    <p class="text-[10px] text-gray-400 mt-1 tabular-nums">
                        @if($event->operatorName)
                            <span class="font-medium text-gray-500">{{ $event->operatorName }}</span>
                            <span class="text-gray-300 mx-0.5">·</span>
                        @endif
                        {{ $event->timestamp->diffForHumans() }}
                    </p>
                </div>

                {{-- Severity badge --}}
                @if($event->severity === 'critical')
                <span class="self-start mt-1 text-[8px] font-bold uppercase tracking-[0.1em] bg-red-100 text-red-600 px-1.5 py-[2px] rounded-md shrink-0 leading-none">CRIT</span>
                @elseif($event->severity === 'warning')
                <span class="self-start mt-1 text-[8px] font-bold uppercase tracking-[0.1em] bg-amber-50 text-amber-600 border border-amber-200/60 px-1.5 py-[2px] rounded-md shrink-0 leading-none">WARN</span>
                @endif
            </div>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center px-5 py-10 gap-3">
            <div class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-[12px] text-gray-400 font-medium">Sin actividad reciente</p>
        </div>
        @endforelse
    </div>

</div>
