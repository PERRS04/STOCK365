<!-- Sidebar OPERADOR -->
<aside class="w-64 bg-stock-primary text-white shadow-lg overflow-y-auto">
    <div class="p-6 border-b border-stock-accent/30">
        <div class="text-2xl font-bold tracking-wider">STOCK 365</div>
        <div class="text-xs text-stock-accent mt-1">OPERADOR</div>
        <div class="text-sm mt-2 text-gray-200">{{ auth()->user()->sede->nombre }}</div>
    </div>

    <nav class="mt-6 space-y-1">
        <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('dashboard') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9M9 9h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Dashboard
        </a>

        <div class="px-6 py-4 text-xs font-semibold text-stock-accent/70 uppercase tracking-wider mt-4">Operaciones</div>

        <a href="{{ route('pos.create') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('pos.*') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            💰 Nueva Venta
        </a>

        <a href="{{ route('cash-closing.create') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('cash-closing.*') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            💵 Cierre de Caja
        </a>

        <a href="{{ route('sales.history') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('sales.history') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Historial de Ventas
        </a>
    </nav>
</aside>
