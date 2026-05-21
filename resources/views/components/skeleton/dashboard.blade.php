<div class="space-y-6 animate-pulse">
    {{-- Stat cards row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        @for ($i = 0; $i < 4; $i++)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-gray-200">
                <div class="h-3 bg-gray-200 rounded w-2/3 mb-3"></div>
                <div class="h-8 bg-gray-200 rounded w-1/2"></div>
            </div>
        @endfor
    </div>

    {{-- Two panels --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>
            <div class="space-y-3">
                @for ($i = 0; $i < 4; $i++)
                    <div class="flex justify-between p-3 bg-gray-50 rounded">
                        <div class="flex-1">
                            <div class="h-3 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-2 bg-gray-100 rounded w-1/2"></div>
                        </div>
                        <div class="h-5 bg-gray-200 rounded w-16 ml-4"></div>
                    </div>
                @endfor
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="h-4 bg-gray-200 rounded w-2/5 mb-4"></div>
            <div class="space-y-3">
                @for ($i = 0; $i < 4; $i++)
                    <div class="p-3 border border-gray-100 rounded">
                        <div class="flex justify-between">
                            <div class="flex-1">
                                <div class="h-3 bg-gray-200 rounded w-1/2 mb-2"></div>
                                <div class="h-2 bg-gray-100 rounded w-2/3"></div>
                            </div>
                            <div class="ml-4">
                                <div class="h-4 bg-gray-200 rounded w-20 mb-1"></div>
                                <div class="h-2 bg-gray-100 rounded w-14"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>
