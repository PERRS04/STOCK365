<div wire:poll.30s class="bg-white rounded-xl border border-gray-200/80 shadow-[0_1px_4px_rgba(0,0,0,0.04)]">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full
                @if(!$session) bg-gray-300
                @elseif($snapshot['status'] === 'ok') bg-emerald-500 animate-pulse
                @elseif($snapshot['status'] === 'baja') bg-amber-400 animate-pulse
                @else bg-red-500 animate-pulse
                @endif">
            </div>
            <h2 class="text-[13px] font-semibold text-gray-800">Caja en vivo</h2>
        </div>
        <div class="flex items-center gap-2">
            @if($session)
                <span class="text-[10px] text-gray-400 font-mono">
                    {{ now()->format('H:i:s') }}
                </span>
                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded
                    @if($snapshot['status'] === 'ok') bg-emerald-100 text-emerald-700
                    @elseif($snapshot['status'] === 'baja') bg-amber-100 text-amber-700
                    @else bg-red-100 text-red-700
                    @endif">
                    @if($snapshot['status'] === 'ok') CAJA OK
                    @elseif($snapshot['status'] === 'baja') CAJA BAJA
                    @else CAJA NEGATIVA
                    @endif
                </span>
            @endif
        </div>
    </div>

    @if(!$session)
        {{-- No active session --}}
        <div class="px-5 py-8 text-center">
            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <p class="text-[12px] text-gray-400">Sin sesión de caja activa</p>
            <a href="{{ route('cash-session.create') }}"
               class="mt-2 inline-block text-[11px] font-medium text-stock-primary hover:underline">
                Abrir caja →
            </a>
        </div>

    @else
        {{-- Breakdown rows --}}
        <div class="px-5 py-1 space-y-0">

            {{-- Opening --}}
            <div class="flex items-center justify-between py-3 border-b border-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-[14px] h-[14px] text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-[12px] text-gray-500">Apertura de caja</span>
                </div>
                <span class="text-[13px] font-medium text-gray-700">
                    +{{ number_format($snapshot['opening'], 2) }}
                </span>
            </div>

            {{-- Ventas efectivo --}}
            <div class="flex items-center justify-between py-3 border-b border-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-[14px] h-[14px] text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span class="text-[12px] text-gray-500">Ventas efectivo</span>
                </div>
                <span class="text-[13px] font-semibold text-emerald-600">
                    +{{ number_format($snapshot['ventas_efectivo'], 2) }}
                </span>
            </div>

            {{-- Ingresos movimientos --}}
            @if($snapshot['ingresos'] > 0)
            <div class="flex items-center justify-between py-3 border-b border-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-[14px] h-[14px] text-teal-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-[12px] text-gray-500">Fondos recibidos</span>
                </div>
                <span class="text-[13px] font-semibold text-teal-600">
                    +{{ number_format($snapshot['ingresos'], 2) }}
                </span>
            </div>
            @endif

            {{-- Egresos / Depósitos enviados --}}
            @if($snapshot['egresos'] > 0)
            <div class="flex items-center justify-between py-3 border-b border-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-[14px] h-[14px] text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    <div>
                        <span class="text-[12px] text-gray-500">Depósitos enviados</span>
                        @if($snapshot['last_egreso'])
                            <p class="text-[10px] text-gray-300 mt-0.5">
                                Último: {{ $snapshot['last_egreso']->approved_at?->diffForHumans() }}
                            </p>
                        @endif
                    </div>
                </div>
                <span class="text-[13px] font-semibold text-amber-600">
                    -{{ number_format($snapshot['egresos'], 2) }}
                </span>
            </div>
            @endif

            {{-- Cortesías --}}
            @if($snapshot['valor_cortesias'] > 0)
            <div class="flex items-center justify-between py-3 border-b border-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-[14px] h-[14px] text-pink-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                    </svg>
                    <span class="text-[12px] text-gray-500">Cortesías aprobadas</span>
                </div>
                <span class="text-[13px] font-semibold text-pink-500">
                    -{{ number_format($snapshot['valor_cortesias'], 2) }}
                </span>
            </div>
            @endif

        </div>

        {{-- Total --}}
        <div class="mx-5 mb-5 mt-1 rounded-xl p-4
            @if($snapshot['status'] === 'ok') bg-emerald-50 border border-emerald-200
            @elseif($snapshot['status'] === 'baja') bg-amber-50 border border-amber-200
            @else bg-red-50 border border-red-200
            @endif">
            <p class="text-[10px] font-semibold uppercase tracking-[0.1em] mb-1
                @if($snapshot['status'] === 'ok') text-emerald-500
                @elseif($snapshot['status'] === 'baja') text-amber-500
                @else text-red-500
                @endif">
                Total en caja
            </p>
            <p class="text-[26px] font-bold leading-none
                @if($snapshot['status'] === 'ok') text-emerald-700
                @elseif($snapshot['status'] === 'baja') text-amber-700
                @else text-red-700
                @endif">
                ${{ number_format($snapshot['total'], 2) }}
            </p>
            <p class="text-[11px] mt-1.5
                @if($snapshot['status'] === 'ok') text-emerald-500/70
                @elseif($snapshot['status'] === 'baja') text-amber-500/70
                @else text-red-500/70
                @endif">
                Sesión activa · {{ $snapshot['session_duration'] }}h
            </p>
        </div>

        {{-- Pending alerts --}}
        @if($snapshot['pending_mov'] > 0 || $snapshot['pending_cor'] > 0)
        <div class="px-5 pb-4 space-y-1.5">
            @if($snapshot['pending_mov'] > 0)
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-100">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                <span class="text-[11px] text-amber-700">
                    {{ $snapshot['pending_mov'] }} movimiento{{ $snapshot['pending_mov'] > 1 ? 's' : '' }} pendiente{{ $snapshot['pending_mov'] > 1 ? 's' : '' }} de aprobación
                </span>
            </div>
            @endif
            @if($snapshot['pending_cor'] > 0)
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-pink-50 border border-pink-100">
                <span class="w-1.5 h-1.5 rounded-full bg-pink-400 shrink-0"></span>
                <span class="text-[11px] text-pink-700">
                    {{ $snapshot['pending_cor'] }} cortesía{{ $snapshot['pending_cor'] > 1 ? 's' : '' }} pendiente{{ $snapshot['pending_cor'] > 1 ? 's' : '' }} de aprobación
                </span>
            </div>
            @endif
        </div>
        @endif

    @endif

</div>
