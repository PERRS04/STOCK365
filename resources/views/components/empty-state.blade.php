@props([
    'title'       => 'Sin resultados',
    'description' => null,
    'icon'        => 'box',
    'colspan'     => null,
])

@php
$iconPaths = [
    'box'      => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7',
    'chart'    => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'cart'     => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    'document' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'clock'    => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'tag'      => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
];
$path = $iconPaths[$icon] ?? $iconPaths['box'];
@endphp

@if($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="py-12 text-center">
            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $path }}"/>
            </svg>
            <p class="text-gray-500 font-medium text-sm">{{ $title }}</p>
            @if($description)
                <p class="text-gray-400 text-xs mt-1">{{ $description }}</p>
            @endif
            @if($slot->isNotEmpty())
                <div class="mt-3">{{ $slot }}</div>
            @endif
        </td>
    </tr>
@else
    <div class="py-12 text-center">
        <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $path }}"/>
        </svg>
        <p class="text-gray-500 font-medium text-sm">{{ $title }}</p>
        @if($description)
            <p class="text-gray-400 text-xs mt-1">{{ $description }}</p>
        @endif
        @if($slot->isNotEmpty())
            <div class="mt-3">{{ $slot }}</div>
        @endif
    </div>
@endif
