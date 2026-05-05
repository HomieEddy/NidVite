<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#D97706">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="NidVite">
    <title>{{ __('Signaler un nid-de-poule') }} - {{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    @laravelPWA
    @livewireStyles
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased">
    <livewire:report-form />
    @livewireScripts
</body>
</html>
