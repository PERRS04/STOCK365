<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'STOCK 365') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* ── Login panel animations ───────────────────────────────── */
        @keyframes loginFadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0);    }
        }
        @keyframes loginBlink {
            0%, 100% { opacity: 1;   }
            50%       { opacity: 0.3; }
        }
        @keyframes loginPulseRing {
            0%   { transform: scale(1);    opacity: 0.5; }
            70%  { transform: scale(1.8);  opacity: 0;   }
            100% { transform: scale(1.8);  opacity: 0;   }
        }
        @keyframes loginGridShift {
            0%   { transform: translateY(0);     }
            100% { transform: translateY(40px);  }
        }
        @keyframes loginScanLine {
            0%   { top: -2px;   opacity: 0;   }
            5%   { opacity: 0.3; }
            95%  { opacity: 0.15; }
            100% { top: 100%;   opacity: 0;   }
        }

        .login-fade-up { animation: loginFadeUp 500ms cubic-bezier(0,0,0.2,1) both; }
        .login-fade-up-2 { animation: loginFadeUp 500ms cubic-bezier(0,0,0.2,1) 80ms both; }
        .login-fade-up-3 { animation: loginFadeUp 500ms cubic-bezier(0,0,0.2,1) 160ms both; }

        .live-dot { animation: loginBlink 2s ease-in-out infinite; }
        .live-ring {
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 1.5px solid rgba(16,185,129,0.5);
            animation: loginPulseRing 2.5s ease-out infinite;
        }

        /* Subtle grid pattern for dark panel */
        .login-grid-bg {
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: loginGridShift 20s linear infinite;
        }

        /* Scan line effect */
        .scan-line {
            position: absolute;
            left: 0; right: 0;
            height: 120px;
            background: linear-gradient(to bottom,
                transparent,
                rgba(79,127,255,0.04) 40%,
                rgba(79,127,255,0.06) 50%,
                rgba(79,127,255,0.04) 60%,
                transparent
            );
            animation: loginScanLine 8s ease-in-out infinite;
            pointer-events: none;
        }
    </style>
</head>
<body class="font-sans antialiased min-h-screen">

{{-- ═══════════════════════════════════════════════════════════
     SPLIT LAYOUT: Dark operational panel + Light form panel
     ═══════════════════════════════════════════════════════════ --}}
<div class="min-h-screen flex">

    {{-- ════════════════════════════════════════════════════════
         LEFT — Operational Brand Panel (desktop only)
         Bloomberg Terminal / Enterprise Platform aesthetic
         ════════════════════════════════════════════════════════ --}}
    <div class="hidden lg:flex lg:w-[420px] xl:w-[460px] flex-col relative overflow-hidden shrink-0"
         style="background: linear-gradient(160deg, #060d1f 0%, #0a1835 35%, #0d2060 70%, #003594 100%)">

        {{-- Grid pattern --}}
        <div class="login-grid-bg absolute inset-0 pointer-events-none"></div>

        {{-- Scan line --}}
        <div class="scan-line"></div>

        {{-- Top glow --}}
        <div class="absolute top-0 left-0 right-0 h-px"
             style="background: linear-gradient(90deg, transparent, rgba(79,127,255,0.5), transparent)"></div>

        {{-- Blue radial glow center --}}
        <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full pointer-events-none"
             style="background: radial-gradient(circle, rgba(0,53,148,0.35) 0%, transparent 70%)"></div>

        <div class="relative flex flex-col h-full px-10 py-10 z-10">

            {{-- Brand ─────────────────────────────────────────── --}}
            <div class="login-fade-up">
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-[22px] font-black text-white tracking-tight leading-none">
                        STOCK<span style="color:#FFD100">365</span>
                    </span>
                    <div class="flex items-center gap-1.5 px-2 py-1 rounded-full"
                         style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.20)">
                        <div class="relative">
                            <div class="live-ring"></div>
                            <div class="w-1.5 h-1.5 rounded-full live-dot" style="background:#10b981"></div>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-widest"
                              style="color:rgba(52,211,153,0.85)">Live</span>
                    </div>
                </div>
                <p class="text-[11px] font-medium tracking-[0.12em] uppercase"
                   style="color:rgba(255,255,255,0.28)">Operational Management Platform</p>
            </div>

            {{-- Headline ─────────────────────────────────────── --}}
            <div class="mt-auto login-fade-up-2">
                <h2 class="text-[30px] xl:text-[34px] font-bold leading-[1.15] mb-4"
                    style="color:rgba(255,255,255,0.92)">
                    Control total de<br>tu operación<br><span style="color:#FFD100">diaria.</span>
                </h2>
                <p class="text-[13px] leading-relaxed mb-8"
                   style="color:rgba(179,204,255,0.55)">
                    Inventario, ventas, cierres de caja y aprobaciones<br>en una sola plataforma diseñada para operar.
                </p>

                {{-- Feature list ──────────────────────────────── --}}
                <ul class="space-y-2.5">
                    @foreach([
                        ['Multi-sede en tiempo real',   '#10b981'],
                        ['POS con búsqueda instantánea','#FFD100'],
                        ['Cierres con flujo aprobado',  '#10b981'],
                        ['Auditoría y reportes completos','#10b981'],
                    ] as [$feature, $color])
                    <li class="flex items-center gap-2.5">
                        <div class="w-4 h-4 rounded-full flex items-center justify-center shrink-0"
                             style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10)">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="{{ $color }}" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-[12px] font-medium" style="color:rgba(203,220,255,0.65)">{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>

                {{-- Operational metrics strip ─────────────────── --}}
                <div class="mt-8 pt-6 border-t" style="border-color:rgba(255,255,255,0.07)">
                    <div class="grid grid-cols-3 gap-3">
                        @foreach([
                            ['Multi-sede', '2+ sedes'],
                            ['Realtime',   '15s sync'],
                            ['Operadores', '∞ users'],
                        ] as [$label, $value])
                        <div class="rounded-xl px-3 py-2.5"
                             style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07)">
                            <p class="text-[15px] font-bold text-white leading-none">{{ $value }}</p>
                            <p class="text-[9px] font-medium uppercase tracking-wider mt-1"
                               style="color:rgba(179,204,255,0.40)">{{ $label }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Footer ───────────────────────────────────────── --}}
            <div class="mt-8">
                <p class="text-[10px]" style="color:rgba(255,255,255,0.18)">
                    © {{ date('Y') }} STOCK365 · Sistema de gestión operacional
                </p>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         RIGHT — Login Form Panel
         ════════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex items-center justify-center min-h-screen p-6"
         style="background: #f4f6fb">

        {{-- Mobile brand (only on small screens) --}}
        <div class="lg:hidden absolute top-6 left-6">
            <span class="text-[18px] font-black text-gray-900 tracking-tight">
                STOCK<span style="color:#003594">365</span>
            </span>
        </div>

        {{ $slot }}
    </div>

</div>

@livewireScripts
</body>
</html>
