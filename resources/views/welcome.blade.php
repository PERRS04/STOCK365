<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>STOCK 365 - Sistema de Inventario y Ventas</title>
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 flex items-center justify-center">
        <div class="text-center text-white px-6">
            <div class="mb-8">
                <div class="w-24 h-24 bg-yellow-400 rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl">
                    <svg class="w-12 h-12 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h1 class="text-5xl font-bold mb-2" style="color: #FFD100;">STOCK 365</h1>
                <p class="text-blue-200 text-xl mb-8">Sistema de Control de Inventario y Ventas</p>
            </div>

            <div class="space-y-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="block w-64 mx-auto bg-yellow-400 text-blue-900 font-bold py-4 px-8 rounded-lg text-lg hover:bg-yellow-300 transition shadow-lg">
                        Ir al Dashboard →
                    </a>
                @else
                    <a href="{{ route('login') }}" class="block w-64 mx-auto bg-yellow-400 text-blue-900 font-bold py-4 px-8 rounded-lg text-lg hover:bg-yellow-300 transition shadow-lg">
                        Iniciar Sesión
                    </a>
                @endauth
            </div>

            <p class="mt-12 text-blue-300 text-sm">
                Sistema Web de Gestión para Sedes Deportivas
            </p>
        </div>
    </div>
</body>
</html>
