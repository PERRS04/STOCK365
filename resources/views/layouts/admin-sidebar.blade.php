<!-- Sidebar BOSS -->
<aside class="w-64 bg-stock-primary text-white shadow-lg overflow-y-auto">
    <div class="p-6 border-b border-stock-accent/30">
        <div class="text-2xl font-bold tracking-wider">STOCK 365</div>
        <div class="text-xs text-stock-accent mt-1">ADMINISTRADOR</div>
    </div>

    <nav class="mt-6 space-y-1">
        <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('dashboard') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9M9 9h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Dashboard
        </a>

        <div class="px-6 py-4 text-xs font-semibold text-stock-accent/70 uppercase tracking-wider mt-4">Operaciones</div>

        <a href="{{ route('products.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('products.*') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8-4 8 4m0 0v10l-8 4-8-4V7m8 4v10m-8-4l8 4 8-4"></path></svg>
            Productos
        </a>

        <a href="{{ route('inventory.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('inventory.*') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
            Inventario
        </a>

        <a href="{{ route('purchase-orders.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('purchase-orders.*') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            Pedidos
        </a>

        <div class="px-6 py-4 text-xs font-semibold text-stock-accent/70 uppercase tracking-wider mt-4">Finanzas</div>

        <a href="{{ route('cash-closings.pending') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('cash-closings.*') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Cierres de Caja
        </a>

        <div class="px-6 py-4 text-xs font-semibold text-stock-accent/70 uppercase tracking-wider mt-4">Reportes</div>

        <a href="{{ route('reports.sales') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('reports.sales') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Ventas
        </a>

        <a href="{{ route('reports.profitability') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('reports.profitability') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Utilidades
        </a>

        <a href="{{ route('reports.top-products') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('reports.top-products') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            Top Productos
        </a>

        <a href="{{ route('reports.comparison') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('reports.comparison') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Comparativo
        </a>

        <div class="px-6 py-4 text-xs font-semibold text-stock-accent/70 uppercase tracking-wider mt-4">Configuración</div>

        <a href="{{ route('sedes.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('sedes.*') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path></svg>
            Sedes
        </a>

        <a href="{{ route('users.index') }}" class="flex items-center px-6 py-3 hover:bg-blue-800 transition {{ request()->routeIs('users.*') ? 'bg-blue-800 border-r-4 border-stock-accent' : '' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM16 11a4 4 0 11-8 0 4 4 0 018 0zM9 20H4v-2a6 6 0 0112 0v2H9z"></path></svg>
            Usuarios
        </a>
    </nav>
</aside>
