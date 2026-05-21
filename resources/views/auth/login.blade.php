<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        {{-- Validation errors --}}
        @if ($errors->any())
        <div class="mb-5 p-3.5 bg-red-50 border border-red-200 rounded-lg">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li class="text-[12px] text-red-600 flex items-start gap-2">
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
        <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 rounded-lg text-[12px] font-medium text-emerald-700">
            {{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
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
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-[14px] text-gray-900 placeholder-gray-300
                               focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition
                               @error('email') border-red-400 @enderror"
                        placeholder="correo@empresa.com"
                    >
                </div>

                <div>
                    <label for="password" class="block text-[11px] font-semibold uppercase tracking-[0.1em] text-gray-500 mb-1.5">
                        Contraseña
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-[14px] text-gray-900
                               focus:outline-none focus:ring-2 focus:ring-stock-primary/20 focus:border-stock-primary transition
                               @error('password') border-red-400 @enderror"
                        placeholder="••••••••"
                    >
                </div>
            </div>

            <div class="flex items-center justify-between mt-5 mb-6">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember_me"
                        class="w-3.5 h-3.5 rounded border-gray-300 text-stock-primary focus:ring-stock-primary focus:ring-offset-0"
                    >
                    <span class="text-[12px] text-gray-500">Recordar sesión</span>
                </label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-[12px] text-stock-primary hover:underline font-medium">
                    ¿Olvidaste tu contraseña?
                </a>
                @endif
            </div>

            <button
                type="submit"
                class="w-full bg-stock-primary hover:bg-blue-800 active:bg-blue-900 text-white font-semibold text-[14px] py-2.5 rounded-lg transition-colors
                       focus:outline-none focus:ring-2 focus:ring-stock-primary focus:ring-offset-2"
            >
                Iniciar sesión
            </button>
        </form>

    </x-authentication-card>
</x-guest-layout>
