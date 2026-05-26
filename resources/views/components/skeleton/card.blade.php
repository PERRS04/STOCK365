@props(['count' => 1])

@for ($i = 0; $i < $count; $i++)
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card p-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 space-y-2.5">
                <div class="h-1.5 skeleton rounded-full w-1/3"></div>
                <div class="h-7 skeleton rounded-lg w-1/2"></div>
            </div>
            <div class="w-10 h-10 skeleton rounded-xl ml-4 shrink-0"></div>
        </div>
        <div class="h-1.5 skeleton rounded-full w-2/3"></div>
    </div>
@endfor
