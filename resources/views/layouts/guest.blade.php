<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'STOCK 365') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">

<div class="min-h-screen flex">

    {{-- Brand Panel (desktop left) --}}
    <div class="hidden lg:flex lg:w-[400px] xl:w-[440px] bg-stock-primary flex-col justify-between p-10 shrink-0">
        <div>
            <span class="text-[20px] font-bold text-white tracking-wide">STOCK<span class="text-stock-accent">365</span></span>
        </div>
        <div>
            <h2 class="text-[28px] font-semibold text-white leading-snug mb-4">
                Control total de<br>tu operación diaria
            </h2>
            <p class="text-blue-200/60 text-[14px] leading-relaxed mb-8">
                Inventario, ventas y cierres de caja en una sola plataforma diseñada para tu equipo.
            </p>
            <ul class="space-y-3">
                @foreach(['Gestión multi-sede en tiempo real', 'POS rápido con búsqueda instantánea', 'Cierres con flujo de aprobación', 'Reportes y auditoría completa'] as $feature)
                <li class="flex items-center gap-3 text-[13px] text-blue-100/70">
                    <span class="w-[18px] h-[18px] rounded-full bg-stock-accent/15 border border-stock-accent/25 flex items-center justify-center shrink-0">
                        <svg class="w-2.5 h-2.5 text-stock-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>
        </div>
        <div>
            <p class="text-[11px] text-blue-200/30">© {{ date('Y') }} STOCK365 · Sistema de gestión</p>
        </div>
    </div>

    {{-- Form Area (right) --}}
    <div class="flex-1 flex items-center justify-center bg-[#f4f6fa] p-6 min-h-screen">
        {{ $slot }}
    </div>

</div>

@livewireScripts
</body>
</html>
