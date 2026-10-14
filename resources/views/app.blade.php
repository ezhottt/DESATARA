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
    @if (app()->environment('demo') && config('public_demo.enabled'))
        <div role="status" style="background:#fff3d6;color:#704b09;text-align:center;padding:10px 14px;font:600 13px system-ui,sans-serif;border-bottom:1px solid #e4c988;">
            DESATARA DEMO PUBLIK — Semua data bersifat simulasi dan tidak mewakili aset desa sebenarnya.
        </div>
    @endif
    @inertia
</body>
</html>
