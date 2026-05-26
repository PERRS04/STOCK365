@props(['rows' => 6, 'cols' => 5])

<div class="bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-3.5 border-b border-gray-100 flex gap-5">
        @for ($c = 0; $c < $cols; $c++)
            <div class="h-2 skeleton rounded-full flex-1"></div>
        @endfor
    </div>

    {{-- Rows --}}
    @for ($r = 0; $r < $rows; $r++)
        <div class="flex items-center gap-5 px-5 py-3.5 border-b border-gray-50/80">
            @for ($c = 0; $c < $cols; $c++)
                @php $widths = ['w-14', 'flex-1', 'w-20', 'w-16', 'w-14']; @endphp
                <div class="h-2.5 skeleton rounded-full {{ $widths[$c % count($widths)] }}"></div>
            @endfor
        </div>
    @endfor

    {{-- Pagination --}}
    <div class="px-5 py-3 flex items-center gap-2">
        <div class="h-7 w-16 skeleton rounded-lg"></div>
        <div class="h-7 w-8 skeleton rounded-lg"></div>
        <div class="h-7 w-8 skeleton rounded-lg"></div>
        <div class="h-7 w-8 skeleton rounded-lg"></div>
        <div class="h-7 w-16 skeleton rounded-lg"></div>
    </div>
</div>
