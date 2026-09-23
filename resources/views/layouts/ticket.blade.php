<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'Ticket de Venta')</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #e9eaf0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            padding: 20px 12px;
        }

        .actions-bar {
            width: 304px;
            margin: 0 auto 14px;
            display: flex;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 10px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.12s;
        }

        .btn-print { background: #003594; color: #fff; }
        .btn-print:hover { background: #002470; }
        .btn-back  { background: #f3f4f6; color: #374151; }
        .btn-back:hover { background: #e5e7eb; }

        .ticket-shell {
            width: 304px; /* ≈ 80 mm at screen dpi */
            margin: 0 auto;
            background: #fff;
            padding: 14px 12px 16px;
            border-radius: 4px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.14);
        }

        /* ── Thermal print rules ── */
        @@page {
            size: 80mm auto;
            margin: 3mm 4mm;
        }

        @@media print {
            body { background: #fff; padding: 0; }
            .actions-bar { display: none !important; }
            .ticket-shell {
                width: 100%;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                margin: 0;
            }
        }

    </style>
    @yield('styles')
</head>
<body>

<div class="actions-bar">
    <button class="btn btn-print" onclick="window.print()">
        &#128424;&nbsp;Imprimir
    </button>
    <a href="{{ route('sales.history') }}" class="btn btn-back">
        &#8592;&nbsp;Volver
    </a>
</div>

<div class="ticket-shell">
    @yield('content')
</div>

</body>
</html>
