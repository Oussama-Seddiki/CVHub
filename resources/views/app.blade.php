<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'CV Hub') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('favicon2.ico') }}?v=3" type="image/x-icon">
        <link rel="shortcut icon" href="{{ asset('favicon2.ico') }}?v=3" type="image/x-icon">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon2.ico') }}?v=3">
        <meta name="theme-color" content="#3b82f6">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:slnt,wght@-11..11,200..1000&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
