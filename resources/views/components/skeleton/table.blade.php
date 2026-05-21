@props(['rows' => 6, 'cols' => 5])

<div class="bg-white rounded-lg shadow overflow-hidden animate-pulse">
    {{-- Header --}}
    <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 flex gap-4">
        @for ($c = 0; $c < $cols; $c++)
            <div class="h-3 bg-gray-200 rounded flex-1"></div>
        @endfor
    </div>

    {{-- Rows --}}
    @for ($r = 0; $r < $rows; $r++)
        <div class="flex items-center gap-4 px-4 py-3 border-b border-gray-100">
            @for ($c = 0; $c < $cols; $c++)
                @php $widths = ['w-16', 'flex-1', 'w-24', 'w-20', 'w-16']; @endphp
                <div class="h-3 bg-gray-100 rounded {{ $widths[$c % count($widths)] }}"></div>
            @endfor
        </div>
    @endfor

    {{-- Pagination --}}
    <div class="px-4 py-3 flex gap-2">
        <div class="h-7 w-16 bg-gray-100 rounded"></div>
        <div class="h-7 w-8 bg-gray-200 rounded"></div>
        <div class="h-7 w-8 bg-gray-100 rounded"></div>
        <div class="h-7 w-8 bg-gray-100 rounded"></div>
        <div class="h-7 w-16 bg-gray-100 rounded"></div>
    </div>
</div>
