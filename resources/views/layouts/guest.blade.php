<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=barlow-condensed:600,700,800|inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-100 antialiased">
        <div class="flex min-h-screen flex-col items-center bg-ink pt-6 sm:justify-center sm:pt-0">
            <div>
                <a href="/" wire:navigate>
                    <span class="grid h-16 w-16 place-items-center rounded-2xl bg-ember-500 font-display text-3xl font-extrabold text-white">F</span>
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden border border-ink-line bg-ink-soft px-6 py-6 sm:max-w-md sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
