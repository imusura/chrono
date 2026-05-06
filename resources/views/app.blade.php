<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'TickIt') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" href="/pwa-192x192.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon-180x180.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#fafafa" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0a0a0f" media="(prefers-color-scheme: dark)">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="TickIt">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    @vite(['resources/css/app.css', 'resources/js/main.ts'])
    <script>
        (function() {
            const theme = localStorage.getItem('appearance-theme') ?? 'system';
            const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) document.documentElement.classList.add('dark');
        })();
    </script>
</head>
<body>
    <div id="app"></div>
</body>
</html>
