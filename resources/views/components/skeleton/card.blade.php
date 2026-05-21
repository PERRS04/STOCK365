@props(['count' => 1])

@for ($i = 0; $i < $count; $i++)
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-gray-200 animate-pulse">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="h-3 bg-gray-200 rounded w-2/3 mb-3"></div>
                <div class="h-8 bg-gray-200 rounded w-1/2"></div>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-lg ml-4"></div>
        </div>
    </div>
@endfor
