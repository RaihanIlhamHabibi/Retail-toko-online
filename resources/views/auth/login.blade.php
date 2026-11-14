<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Validation Errors -->
    <x-auth-validation-errors class="mb-4" :errors="$errors" />

    <div class="auth-heading">
        <h1>{{ __('Masuk ke Abangkuh') }}</h1>
        <p>{{ __('Masukkan email dan kata sandi. Anda akan diarahkan sesuai akses akun.') }}</p>
    </div>

    <form method="POST" action="{{ airoute('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mt-4">
            <x-label for="email" :value="__('Email')" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" autocomplete="email" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-label for="password" :value="__('Kata sandi')" />
            <x-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                autocomplete="current-password"
                required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ml-2 text-sm text-gray-600">{{ __('Ingat saya') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ airoute('password.request') }}">
                    {{ __('Lupa kata sandi?') }}
                </a>
            @endif

            <x-button class="ml-3">
                {{ __('Masuk') }}
            </x-button>
        </div>
    </form>

    <p class="auth-register-link">{{ __('Belum punya akun?') }} <a href="{{ airoute('register') }}">{{ __('Daftar di sini') }}</a></p>
</x-guest-layout>
