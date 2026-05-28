<x-guest-layout>
<div class="w-full max-w-sm login-fade-up-3">

    {{-- Logo --}}
    <div class="mb-8">
        <a href="/" class="inline-block">
            <span class="text-[26px] font-black tracking-tight" style="color:#111827">
                STOCK<span style="color:#003594">365</span>
            </span>
        </a>
        <p class="text-[12px] font-medium mt-1" style="color:#9ca3af">
            Inicia sesión en tu cuenta
        </p>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
    <div class="mb-5 p-3.5 rounded-xl" style="background:#fef2f2; border:1px solid #fecaca">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
            <li class="text-[12px] flex items-start gap-2" style="color:#dc2626">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ $error }}
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (session('status'))
    <div class="mb-5 p-3.5 rounded-xl text-[12px] font-medium"
         style="background:#f0fdf4; border:1px solid #bbf7d0; color:#059669">
        {{ session('status') }}
    </div>
    @endif

    {{-- Form card --}}
    <div class="bg-white rounded-2xl px-7 py-7"
         style="border:1px solid rgba(0,0,0,0.07); box-shadow:0 2px 20px rgba(0,0,0,0.06), 0 8px 40px rgba(0,0,0,0.04)">

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="space-y-4">

                {{-- Email --}}
                <div>
                    <label for="email"
                           class="block text-[11px] font-bold uppercase tracking-[0.10em] mb-1.5"
                           style="color:#6b7280">
                        Correo electrónico
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="correo@empresa.com"
                        class="w-full rounded-xl px-3.5 py-2.5 text-[14px] transition-all duration-150
                               focus:outline-none
                               @error('email') border-red-400 @enderror"
                        style="border:1px solid rgba(0,0,0,0.12); color:#111827; background:#fff;
                               --tw-ring-shadow:none;"
                        onfocus="this.style.borderColor='#003594'; this.style.boxShadow='0 0 0 3px rgba(0,53,148,0.12)'"
                        onblur="this.style.borderColor='rgba(0,0,0,0.12)'; this.style.boxShadow='none'"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label for="password"
                           class="block text-[11px] font-bold uppercase tracking-[0.10em] mb-1.5"
                           style="color:#6b7280">
                        Contraseña
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full rounded-xl px-3.5 py-2.5 text-[14px] transition-all duration-150
                               focus:outline-none
                               @error('password') border-red-400 @enderror"
                        style="border:1px solid rgba(0,0,0,0.12); color:#111827; background:#fff;"
                        onfocus="this.style.borderColor='#003594'; this.style.boxShadow='0 0 0 3px rgba(0,53,148,0.12)'"
                        onblur="this.style.borderColor='rgba(0,0,0,0.12)'; this.style.boxShadow='none'"
                    >
                </div>
            </div>

            {{-- Remember + forgot --}}
            <div class="flex items-center justify-between mt-4 mb-5">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember_me"
                        class="w-3.5 h-3.5 rounded"
                        style="accent-color:#003594"
                    >
                    <span class="text-[12px]" style="color:#6b7280">Recordar sesión</span>
                </label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-[12px] font-semibold hover:underline transition"
                   style="color:#003594">
                    ¿Olvidaste tu contraseña?
                </a>
                @endif
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                class="w-full h-11 rounded-xl font-bold text-[14px] text-white transition-all duration-150
                       focus:outline-none active:scale-[0.98]"
                style="background:#003594; box-shadow:0 4px 16px rgba(0,53,148,0.28);"
                onmouseover="this.style.background='#002470'"
                onmouseout="this.style.background='#003594'"
                onfocus="this.style.boxShadow='0 0 0 3px rgba(0,53,148,0.20), 0 4px 16px rgba(0,53,148,0.28)'"
                onblur="this.style.boxShadow='0 4px 16px rgba(0,53,148,0.28)'"
            >
                Iniciar sesión
            </button>

        </form>
    </div>

    {{-- Footer --}}
    <p class="text-center text-[11px] mt-6" style="color:#c4c9d4">
        © {{ date('Y') }} STOCK365 · Plataforma operacional
    </p>
</div>
</x-guest-layout>
