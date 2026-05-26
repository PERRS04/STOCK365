<div class="space-y-5">
    {{-- KPI strip --}}
    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-card">
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-gray-100">
            @for ($i = 0; $i < 4; $i++)
                <div class="px-7 py-5 space-y-3">
                    <div class="h-1.5 skeleton rounded-full w-1/3"></div>
                    <div class="h-7 skeleton rounded-lg w-1/2"></div>
                    <div class="h-1.5 skeleton rounded-full w-2/5"></div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Timeline | Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- Timeline panel --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2.5">
                <div class="w-2 h-2 skeleton rounded-full"></div>
                <div class="h-2 skeleton rounded-full w-28"></div>
            </div>
            <div class="divide-y divide-gray-50/80">
                @for ($i = 0; $i < 6; $i++)
                    <div class="flex items-center gap-3.5 px-5 py-3">
                        <div class="w-2 h-2 skeleton rounded-full shrink-0"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="h-2 skeleton rounded-full w-1/2"></div>
                            <div class="h-1.5 skeleton rounded-full w-1/3"></div>
                        </div>
                        <div class="text-right space-y-1.5 shrink-0">
                            <div class="h-2.5 skeleton rounded-full w-14"></div>
                            <div class="h-1.5 skeleton rounded-full w-8 ml-auto"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- Quick actions panel --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200/60 shadow-card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <div class="h-2 skeleton rounded-full w-24"></div>
            </div>
            <div class="p-4 space-y-2.5">
                @for ($i = 0; $i < 4; $i++)
                    <div class="flex items-center gap-3 px-4 py-3 rounded-lg border border-gray-100">
                        <div class="w-4 h-4 skeleton rounded shrink-0"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="h-2 skeleton rounded-full w-1/2"></div>
                            <div class="h-1.5 skeleton rounded-full w-2/3"></div>
                        </div>
                        <div class="w-3 h-3 skeleton rounded shrink-0"></div>
                    </div>
                @endfor
            </div>
        </div>

    </div>
</div>
