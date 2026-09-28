<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B2E5B">
    <link rel="icon" type="image/png" href="{{ asset('logo/desatara-symbol.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo/desatara-symbol.png') }}">
    <title inertia>{{ config('app.name', 'DESATARA') }}</title>
    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
