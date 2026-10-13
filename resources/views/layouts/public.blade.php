<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
        <meta name="description" content="{{ $description ?? 'Entrenamientos en video para todos los niveles.' }}">

        <!-- Fonts: Barlow Condensed (títulos) + Inter (texto) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=barlow-condensed:600,700,800|inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ink font-sans text-slate-100 antialiased">
        <header class="sticky top-0 z-40 border-b border-ink-line/70 bg-ink/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-ember-500 font-display text-lg font-extrabold text-white">F</span>
                    <span class="font-display text-2xl font-extrabold uppercase tracking-wide text-white">
                        Fit<span class="text-volt">nes</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-8 sm:flex">
                    <a href="{{ route('home') }}" class="text-sm font-medium text-slate-300 transition hover:text-volt">Inicio</a>
                    <a href="{{ route('planes') }}" class="text-sm font-medium text-slate-300 transition hover:text-volt">Planes</a>
                </nav>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary !px-4 !py-2 text-sm">Mi panel</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 transition hover:text-volt">Ingresar</a>
                        <a href="{{ route('register') }}" class="btn-primary !px-4 !py-2 text-sm">Crear cuenta</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-24 border-t border-ink-line/70">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-slate-400 sm:flex-row sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. Todos los derechos reservados.</p>
                <a href="{{ route('planes') }}" class="transition hover:text-volt">Ver planes</a>
            </div>
        </footer>
    </body>
</html>
