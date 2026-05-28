@php
    $isCrit     = $alert->isCritical();
    $color      = $alert->color(); // 'red' | 'amber'
    $iconBg     = $isCrit ? 'bg-red-50'    : 'bg-amber-50';
    $iconClr    = $isCrit ? 'text-red-500'  : 'text-amber-600';
    $barClr     = $isCrit ? 'bg-red-500'    : 'bg-amber-400';
    $sessionId  = $alert->payload['session_id'] ?? null;
    $actionRoute = $alert->payload['action_route'] ?? null;
    $viewRoute   = $alert->payload['view_route'] ?? null;
    $canForceClose = $alert->hasForceClose() && $sessionId;
@endphp
<div class="flex items-stretch hover:bg-gray-50/60 transition-colors animate-feed-in">

    {{-- Priority bar (3px) --}}
    <div class="w-[3px] shrink-0 {{ $barClr }}"></div>

    {{-- Content --}}
    <div class="flex items-start gap-3 px-4 py-3 flex-1 min-w-0">

        {{-- Semantic icon --}}
        <div class="w-7 h-7 rounded-lg {{ $iconBg }} {{ $iconClr }} flex items-center justify-center shrink-0 mt-[1px]">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $alert->iconPath() }}"/>
            </svg>
        </div>

        {{-- Text block --}}
        <div class="flex-1 min-w-0">
            <p class="text-[12px] font-semibold text-gray-800 leading-tight">{{ $alert->title }}</p>
            <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">{{ $alert->subtitle }}</p>
            {{-- Meta: sede + operator --}}
            @if($alert->sedeName || $alert->operatorName)
            <p class="text-[10px] text-gray-400 mt-1 tabular-nums">
                @if($alert->sedeName)
                    <span class="font-medium">{{ $alert->sedeName }}</span>
                @endif
                @if($alert->sedeName && $alert->operatorName)
                    <span class="text-gray-300 mx-0.5">·</span>
                @endif
                @if($alert->operatorName){{ $alert->operatorName }}@endif
                <span class="text-gray-300 mx-0.5">·</span>
                {{ $alert->detectedAt->diffForHumans() }}
            </p>
            @else
            <p class="text-[10px] text-gray-400 mt-1 tabular-nums">{{ $alert->detectedAt->diffForHumans() }}</p>
            @endif
        </div>

        {{-- Quick actions --}}
        <div class="flex flex-col gap-1 shrink-0 items-end ml-1">
            @if($canForceClose)
            <button wire:click="promptForceClose({{ $sessionId }})"
                    class="text-[9px] font-bold uppercase tracking-[0.10em] px-2 py-1 rounded-lg bg-red-100 hover:bg-red-200 text-red-600 border border-red-200/80 transition-colors leading-none">
                Force Close
            </button>
            @endif
            @if($actionRoute)
            <a href="{{ $actionRoute }}"
               class="text-[9px] font-bold uppercase tracking-[0.10em] px-2 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 border border-gray-200 transition-colors leading-none">
                Ver →
            </a>
            @elseif($viewRoute)
            <a href="{{ $viewRoute }}"
               class="text-[9px] font-bold uppercase tracking-[0.10em] px-2 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 border border-gray-200 transition-colors leading-none">
                Ver →
            </a>
            @endif
        </div>

    </div>
</div>
