<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @if (app()->environment('testing'))
            <style>
                body {
                    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                }
            </style>
        @else
            @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @endif
        @inertiaHead
    </head>
    <body class="min-vh-100 bg-body-secondary">
        @inertia
    </body>
</html>

