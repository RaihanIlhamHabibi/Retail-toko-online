<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="auth-page">
            <main class="auth-shell">
                <a class="auth-brand" href="/" aria-label="{{ __('Abangkuh - Beranda') }}">
                    <span class="auth-brand-name">Abangkuh</span>
                    <span class="auth-brand-tagline">{{ __('Retail Toko Online') }}</span>
                </a>

                @if (Route::currentRouteName() && Route::has(Route::currentRouteName()))
                    @php($localeRoute = Route::currentRouteName())
                    @php($localeParams = request()->route()?->parameters() ?? [])
                    <nav class="auth-locale-switch" aria-label="{{ __('Pilih bahasa') }}">
                        <a href="{{ airoute($localeRoute, array_merge($localeParams, ['locale' => 'id'])) }}">ID</a>
                        <span aria-hidden="true">|</span>
                        <a href="{{ airoute($localeRoute, array_merge($localeParams, ['locale' => 'en'])) }}">EN</a>
                    </nav>
                @endif

                <section class="auth-card">
                    {{ $slot }}
                </section>
            </main>
        </div>

        <style>
            .auth-page {
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 2rem 1rem;
                background: radial-gradient(circle at top, #e0f2fe 0, #f8fafc 48%, #eef2f7 100%);
                color: #0f172a;
            }

            .auth-shell {
                width: min(100%, 28rem);
            }

            .auth-brand {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-bottom: 1.5rem;
                text-decoration: none;
            }

            .auth-brand-name {
                color: #123b56;
                font-size: 2rem;
                font-weight: 800;
                letter-spacing: -0.04em;
                line-height: 1.1;
            }

            .auth-brand-tagline {
                margin-top: 0.45rem;
                color: #475569;
                font-size: 0.75rem;
                letter-spacing: 0.14em;
                text-transform: uppercase;
            }

            .auth-locale-switch {
                margin: 0 0 0.75rem;
                color: #475569;
                font-size: 0.8rem;
                text-align: center;
            }

            .auth-locale-switch a {
                color: #0f4c6e;
                font-weight: 600;
                text-decoration: none;
            }

            .auth-card {
                width: 100%;
                padding: 2rem;
                border: 1px solid #e2e8f0;
                border-radius: 1rem;
                background: #fff !important;
                box-shadow: 0 20px 50px rgb(15 23 42 / 10%);
                color: #0f172a;
            }

            .auth-heading {
                margin-bottom: 1.5rem;
            }

            .auth-heading h1 {
                margin: 0;
                color: #0f172a;
                font-size: 1.5rem;
                font-weight: 700;
            }

            .auth-heading p {
                margin: 0.5rem 0 0;
                color: #475569;
                font-size: 0.9rem;
            }

            .auth-admin-link,
            .auth-register-link {
                margin: 1.5rem 0 0;
                color: #475569;
                font-size: 0.875rem;
                text-align: center;
            }

            .auth-admin-link a,
            .auth-register-link a {
                color: #0f4c6e;
                font-weight: 600;
                text-decoration: underline;
            }

            .auth-card label,
            .auth-card .text-gray-600 {
                color: #334155 !important;
            }

            .auth-card input[type="email"],
            .auth-card input[type="password"] {
                background: #fff !important;
                border-color: #cbd5e1 !important;
                color: #0f172a !important;
            }

            .auth-card input[type="email"]:focus,
            .auth-card input[type="password"]:focus {
                border-color: #0f766e !important;
                box-shadow: 0 0 0 2px rgb(15 118 110 / 20%) !important;
                outline: none;
            }

            .auth-card button[type="submit"] {
                border: 0 !important;
                border-radius: 0.6rem;
                background: #0f4c6e !important;
                color: #fff !important;
                padding: 0.7rem 1.25rem;
            }

            .auth-card button[type="submit"]:hover {
                background: #0b3b56 !important;
            }

            @media (max-width: 480px) {
                .auth-card {
                    padding: 1.5rem;
                }
            }
        </style>
    </body>
</html>
