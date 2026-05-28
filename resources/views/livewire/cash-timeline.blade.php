{{--
  Cash Timeline — Financial event stream.
  Pending items appear first with amber warning context.
  Each completed event has a 3px semantic accent bar, icon, dominant amount.
  wire:poll.keep-alive stops polling when the browser tab is hidden.
--}}
<div wire:poll.20s.keep-alive
     wire:loading.class.delay.long="opacity-50"
     class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden transition-opacity duration-300">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
            @if($session)
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
            @else
                <span class="w-2 h-2 rounded-full bg-gray-300 shrink-0"></span>
            @endif
            <h2 class="text-[13px] font-bold text-gray-800 tracking-tight">Timeline Financiero</h2>
        </div>
        @if($session)
        <div class="flex items-center gap-3">
            <span class="text-[10px] text-gray-400 font-mono">
                Desde {{ $session->opened_at->format('H:i') }} · {{ $session->duration }}h activa
            </span>
            <span class="text-[10px] font-mono text-gray-300 tabular-nums">{{ now()->format('H:i') }}</span>
        </div>
        @endif
    </div>

    @if(!$session || $events->isEmpty())
        <div class="px-5 py-12 text-center">
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-[13px] font-medium text-gray-500">
                {{ !$session ? 'Sin sesión activa' : 'Sin eventos registrados' }}
            </p>
            <p class="text-[12px] text-gray-400 mt-0.5">
                Los eventos de caja aparecen aquí en tiempo real
            </p>
        </div>

    @else

    @php
        $pending   = $events->where('isPending', true)->values();
        $completed = $events->where('isPending', false)->values();
    @endphp

    {{-- ── Pending section ─────────────────────────────────────────────── --}}
    @if($pending->isNotEmpty())
    <div class="border-b border-amber-100 bg-amber-50/50">

        <div class="px-5 pt-3 pb-1.5 flex items-center gap-2">
            <span class="relative flex h-[5px] w-[5px] shrink-0">
                <span class="animate-status-ring absolute inline-flex h-full w-full rounded-full bg-amber-400"></span>
                <span class="relative inline-flex rounded-full h-[5px] w-[5px] bg-amber-400"></span>
            </span>
            <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-amber-600">
                {{ $pending->count() }} {{ $pending->count() === 1 ? 'evento pendiente' : 'eventos pendientes' }} · Esperando aprobación
            </span>
        </div>

        @foreach($pending as $event)
        <div wire:key="ev-{{ $event->id }}"
             class="relative flex items-center gap-3 px-5 py-3 border-t border-amber-100/70">
            <div class="absolute left-0 top-0 bottom-0 w-[3px] {{ $event->barClr() }}"></div>

            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ $event->iconBg() }}">
                <svg class="w-4 h-4 {{ $event->iconClr() }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="{{ $event->iconPath() }}"/>
                </svg>
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-[12px] font-semibold text-gray-700">{{ $event->label }}</p>
                @if($event->detail)
                <p class="text-[10px] text-gray-400 truncate mt-0.5">{{ $event->detail }}</p>
                @endif
            </div>

            <div class="text-right shrink-0">
                @if($event->amount > 0)
                <p class="font-bold tabular-nums num {{ $event->amountSize() }} {{ $event->amountClr() }}">
                    {{ $event->formattedAmount() }}
                </p>
                @endif
                <p class="text-[10px] text-amber-500/70 font-mono mt-0.5">{{ $event->timestamp->format('H:i') }}</p>
            </div>
        </div>
        @endforeach

    </div>
    @endif

    {{-- ── Completed event stream ───────────────────────────────────────── --}}
    <div class="overflow-y-auto max-h-[440px]">

        @foreach($completed as $event)
        @php $isOpener = $event->type === \App\Enums\CashEventType::SESSION_OPEN; @endphp
        <div wire:key="ev-{{ $event->id }}"
             class="relative flex items-center gap-3 px-5 py-3 border-b border-gray-50/80
                 {{ $isOpener ? 'bg-blue-50/30' : 'hover:bg-gray-50/60' }} transition-colors">

            <div class="absolute left-0 top-0 bottom-0 w-[3px] {{ $event->barClr() }}"></div>

            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0
                        {{ $event->iconBg() }}
                        {{ $event->isHighValue() ? 'ring-1 ring-offset-1 ring-current/20' : '' }}">
                <svg class="w-4 h-4 {{ $event->iconClr() }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="{{ $event->iconPath() }}"/>
                </svg>
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-[12px] font-semibold {{ $isOpener ? 'text-blue-700' : 'text-gray-800' }}">
                    {{ $event->label }}
                </p>
                @if($event->detail)
                <p class="text-[10px] text-gray-400 truncate mt-0.5">{{ $event->detail }}</p>
                @endif
            </div>

            <div class="text-right shrink-0">
                @if($event->amount > 0 || $isOpener)
                <p class="font-bold tabular-nums num {{ $event->amountSize() }} {{ $event->amountClr() }}">
                    {{ $event->formattedAmount() }}
                </p>
                @endif
                <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $event->timestamp->format('H:i') }}</p>
            </div>

        </div>
        @endforeach

    </div>

    @endif

</div>
