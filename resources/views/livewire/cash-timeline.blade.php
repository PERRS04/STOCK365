<div wire:poll.20s
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
            <h2 class="text-[13px] font-semibold text-gray-800">Timeline de caja</h2>
        </div>
        @if($session)
            <span class="text-[10px] font-mono text-gray-400 bg-gray-50 px-2 py-1 rounded-md border border-gray-100">{{ now()->format('H:i:s') }}</span>
        @endif
    </div>

    @if(!$session || $events->isEmpty())
        <div class="px-5 py-12 text-center">
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-[13px] font-medium text-gray-500">{{ !$session ? 'Sin sesión activa' : 'Sin eventos registrados' }}</p>
            <p class="text-[12px] text-gray-400 mt-0.5">Los eventos de caja aparecen aquí en tiempo real</p>
        </div>
    @else
        <div class="overflow-y-auto max-h-[420px] divide-y divide-gray-50/80">
            @foreach($events as $event)
            @php
                $dotClr = match($event['type']) {
                    'venta'    => 'bg-emerald-500',
                    'cortesia' => 'bg-pink-400',
                    'apertura' => 'bg-blue-400',
                    default    => $event['sign'] === '-' ? 'bg-amber-400' : 'bg-teal-400',
                };
                $amtClr = match($event['type']) {
                    'venta'    => 'text-emerald-600',
                    'cortesia' => 'text-pink-500',
                    'apertura' => 'text-blue-600',
                    default    => $event['sign'] === '-' ? 'text-amber-600' : 'text-teal-600',
                };
                $rowBg = $event['type'] === 'apertura' ? 'bg-blue-50/30' : '';
                $t = $event['time'];
                $timeStr = ($t instanceof \Carbon\Carbon) ? $t->format('H:i') : \Carbon\Carbon::parse($t)->format('H:i');
            @endphp
            <div class="flex items-center gap-3.5 px-5 py-3 {{ $rowBg }} hover:bg-gray-50/70 transition-colors">
                <span class="w-2 h-2 rounded-full shrink-0 {{ $dotClr }}"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-[12px] font-semibold text-gray-800">{{ $event['label'] }}</p>
                    @if(!empty($event['detail']))
                        <p class="text-[10px] text-gray-400 truncate mt-0.5">{{ $event['detail'] }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <p class="text-[13px] font-semibold {{ $amtClr }} num">
                        {{ $event['sign'] }}${{ number_format($event['amount'], 2) }}
                    </p>
                    <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $timeStr }}</p>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</div>
