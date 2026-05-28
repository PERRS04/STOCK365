@if ($paginator->hasPages())
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
     class="flex items-center justify-between px-1 mt-4">

    {{-- Mobile --}}
    <div class="flex flex-1 justify-between sm:hidden gap-2">
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm btn-secondary opacity-40 cursor-not-allowed">
                {{ __('pagination.previous') }}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-secondary">
                {{ __('pagination.previous') }}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-secondary">
                {{ __('pagination.next') }}
            </a>
        @else
            <span class="btn btn-sm btn-secondary opacity-40 cursor-not-allowed">
                {{ __('pagination.next') }}
            </span>
        @endif
    </div>

    {{-- Desktop --}}
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">

        <p class="text-[11px] text-gray-400 tabular-nums">
            {!! __('Showing') !!}
            <span class="font-semibold text-gray-600">{{ $paginator->firstItem() }}</span>
            {!! __('–') !!}
            <span class="font-semibold text-gray-600">{{ $paginator->lastItem() }}</span>
            {!! __('of') !!}
            <span class="font-semibold text-gray-600">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-ghost opacity-30 cursor-not-allowed" aria-disabled="true">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="btn btn-sm btn-ghost"
                   rel="prev" aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-[11px] text-gray-300 select-none">···</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="btn btn-sm btn-primary min-w-[32px] tabular-nums">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="btn btn-sm btn-ghost min-w-[32px] tabular-nums text-gray-500 hover:text-gray-800">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="btn btn-sm btn-ghost"
                   rel="next" aria-label="{{ __('pagination.next') }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="btn btn-sm btn-ghost opacity-30 cursor-not-allowed" aria-disabled="true">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif

        </div>
    </div>
</nav>
@endif
