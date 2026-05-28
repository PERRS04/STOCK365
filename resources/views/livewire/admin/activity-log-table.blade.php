<div class="space-y-4">
    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200/60 px-5 py-4">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Usuario, descripción o IP…"
                        class="input input-sm w-full pl-9"/>
                </div>
            </div>

            {{-- Action type --}}
            <div>
                <select wire:model.live="action" class="input input-sm w-full">
                    <option value="">Todos los módulos</option>
                    @foreach($this->actionGroups as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Role --}}
            <div>
                <select wire:model.live="role" class="input input-sm w-full">
                    <option value="">Todos los roles</option>
                    <option value="boss">Boss</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="operador">Operador</option>
                </select>
            </div>

            {{-- Sede --}}
            <div>
                <select wire:model.live="sedeId" class="input input-sm w-full">
                    <option value="">Todas las sedes</option>
                    @foreach($this->sedes as $sede)
                        <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Dates --}}
            <div class="flex gap-2 items-center">
                <input type="date" wire:model.live="startDate" class="input input-sm flex-1"/>
                <span class="text-gray-400 text-[10px]">→</span>
                <input type="date" wire:model.live="endDate" class="input input-sm flex-1"/>
            </div>
        </div>

        @if($search || $action || $role || $sedeId || $startDate || $endDate)
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                <span class="text-[11px] text-gray-500">{{ $this->logs->total() }} registro(s) encontrado(s)</span>
                <button wire:click="clear" class="text-[11px] text-red-500 hover:text-red-700 transition">
                    Limpiar filtros ×
                </button>
            </div>
        @endif

        <div wire:loading.delay class="flex items-center gap-2 text-[11px] text-gray-500 mt-2">
            <svg class="w-3 h-3 animate-spin text-stock-primary" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            Filtrando…
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200/60 overflow-hidden transition-opacity duration-150" wire:loading.class.delay="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="border-b border-gray-100">
                    <tr>
                        <th class="text-left py-3 px-4 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 whitespace-nowrap">Timestamp</th>
                        <th class="text-left py-3 px-4 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Usuario</th>
                        <th class="text-left py-3 px-4 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Acción</th>
                        <th class="text-left py-3 px-4 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Descripción</th>
                        <th class="text-left py-3 px-4 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Cambios</th>
                        <th class="text-left py-3 px-4 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($this->logs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors align-top">
                            {{-- Timestamp --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                <p class="text-gray-800 font-mono text-xs">{{ $log->created_at->format('d/m/Y') }}</p>
                                <p class="text-gray-500 font-mono text-xs">{{ $log->created_at->format('H:i:s') }}</p>
                            </td>

                            {{-- User --}}
                            <td class="py-3 px-4">
                                <p class="font-medium text-gray-800 text-xs">{{ $log->user_name }}</p>
                                <span class="inline-block px-1.5 py-0.5 rounded text-xs font-medium mt-0.5
                                    {{ $log->user_role === 'boss' ? 'bg-purple-100 text-purple-700' : ($log->user_role === 'supervisor' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $log->user_role }}
                                </span>
                                @if($log->sede)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $log->sede->nombre }}</p>
                                @endif
                            </td>

                            {{-- Action badge --}}
                            <td class="py-3 px-4 whitespace-nowrap">
                                @php
                                    $prefix = explode('.', $log->action)[0] ?? '';
                                    $colors = [
                                        'sale'      => 'bg-green-100 text-green-700',
                                        'product'   => 'bg-yellow-100 text-yellow-700',
                                        'inventory' => 'bg-orange-100 text-orange-700',
                                        'closing'   => 'bg-blue-100 text-blue-700',
                                        'user'      => 'bg-red-100 text-red-700',
                                    ];
                                    $color = $colors[$prefix] ?? 'bg-gray-100 text-gray-600';
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                    {{ $log->action }}
                                </span>
                            </td>

                            {{-- Description --}}
                            <td class="py-3 px-4 max-w-xs">
                                <p class="text-gray-700 text-xs leading-relaxed">{{ $log->description }}</p>
                                @if($log->model_type && $log->model_id)
                                    <p class="text-gray-400 text-xs mt-0.5 font-mono">{{ $log->model_type }}#{{ $log->model_id }}</p>
                                @endif
                            </td>

                            {{-- Old → New values --}}
                            <td class="py-3 px-4 max-w-xs">
                                @if($log->old_values || $log->new_values)
                                    <div class="space-y-0.5">
                                        @foreach(array_keys(array_merge($log->old_values ?? [], $log->new_values ?? [])) as $key)
                                            @php
                                                $old = $log->old_values[$key] ?? null;
                                                $new = $log->new_values[$key] ?? null;
                                                $changed = $old !== $new;
                                            @endphp
                                            <div class="flex items-center gap-1 text-xs">
                                                <span class="text-gray-500 font-medium">{{ $key }}:</span>
                                                @if($old !== null)
                                                    <span class="{{ $changed ? 'line-through text-red-400' : 'text-gray-600' }}">{{ $old }}</span>
                                                @endif
                                                @if($changed && $new !== null)
                                                    <span class="text-gray-400">→</span>
                                                    <span class="text-green-600 font-medium">{{ $new }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>

                            {{-- IP --}}
                            <td class="py-3 px-4">
                                <span class="text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state :colspan="6" icon="document" title="Sin registros de auditoría"
                            description="Las acciones del sistema aparecerán aquí"/>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3.5 border-t border-gray-100 flex items-center justify-between">
            <p class="text-[11px] text-gray-400">{{ $this->logs->total() }} registros totales</p>
            {{ $this->logs->links() }}
        </div>
    </div>
</div>
