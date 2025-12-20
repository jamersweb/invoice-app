<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', 'resources/css/app.css', 'resources/css/custom.css', 'resources/custom-js/custom-index.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-dark-primary text-dark-text-primary {{ app()->getLocale() === 'ar' ? 'rtl' : '' }}">
        @inertia
    </body>
</html>
