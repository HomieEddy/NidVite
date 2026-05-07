<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#D97706">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="NidVite">
    <meta name="format-detection" content="telephone=no">
    
    <title>@yield('title', config('app.name'))</title>
    
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    
    @laravelPWA
    @livewireStyles
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="citizen-container no-overscroll">
    {{-- Skip to content for accessibility --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-amber-600 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg">
        {{ __('Accéder au contenu principal') }}
    </a>
    
    {{-- Header --}}
    <header class="citizen-header text-white safe-top sticky top-0 z-40 shadow-lg">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <a href="/" class="flex items-center space-x-2.5 btn-touch shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-lg font-extrabold tracking-tight font-display">{{ config('app.name') }}</span>
            </a>

            {{-- Desktop nav links --}}
            <nav class="hidden md:flex items-center space-x-1" aria-label="{{ __('Navigation principale') }}">
                <a href="/"
                   class="px-3 py-1.5 rounded-lg text-sm font-semibold transition btn-touch inline-flex items-center justify-center interactive-lift
                   {{ request()->is('/') ? 'bg-white/20 text-white' : 'text-amber-100 hover:text-white hover:bg-white/10' }}">
                    {{ __('Accueil') }}
                </a>
                <a href="{{ route('report.create') }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-semibold transition btn-touch inline-flex items-center justify-center interactive-lift
                   {{ request()->routeIs('report.create') ? 'bg-white/20 text-white' : 'text-amber-100 hover:text-white hover:bg-white/10' }}">
                    {{ __('Signaler') }}
                </a>
                <a href="{{ route('map.public') }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-semibold transition btn-touch inline-flex items-center justify-center interactive-lift
                   {{ request()->routeIs('map.public') ? 'bg-white/20 text-white' : 'text-amber-100 hover:text-white hover:bg-white/10' }}">
                    {{ __('Carte') }}
                </a>
            </nav>
            
            <div class="flex items-center space-x-1 shrink-0">
                <a href="{{ route('locale.switch', 'fr') }}" 
                   class="px-2.5 py-1 rounded-md text-sm font-medium transition btn-touch inline-flex items-center justify-center
                   {{ app()->getLocale() === 'fr' ? 'bg-white/20 text-white' : 'text-amber-100 hover:text-white hover:bg-white/10' }}">
                    FR
                </a>
                <a href="{{ route('locale.switch', 'en') }}" 
                   class="px-2.5 py-1 rounded-md text-sm font-medium transition btn-touch inline-flex items-center justify-center
                   {{ app()->getLocale() === 'en' ? 'bg-white/20 text-white' : 'text-amber-100 hover:text-white hover:bg-white/10' }}">
                    EN
                </a>
            </div>
        </div>
    </header>
    
    {{-- Main Content --}}
    <main id="main-content" class="flex-1 w-full">
        @yield('content')
    </main>
    
    {{-- Bottom Navigation (mobile only) --}}
    <nav class="sticky bottom-0 z-1001 citizen-glass-nav safe-bottom shadow-[0_-4px_16px_-1px_rgba(0,0,0,0.1)] md:hidden" aria-label="{{ __('Navigation mobile') }}">
        <div class="max-w-3xl mx-auto px-2">
            <div class="flex items-center justify-around">
                <a href="{{ route('report.create') }}" 
                   class="flex flex-col items-center justify-center py-2 px-3 text-xs font-semibold transition btn-touch interactive-lift
                   {{ request()->routeIs('report.create') ? 'text-amber-600' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>{{ __('Signaler') }}</span>
                </a>
                
                <a href="{{ route('map.public') }}" 
                         class="flex flex-col items-center justify-center py-2 px-3 text-xs font-semibold transition btn-touch interactive-lift
                   {{ request()->routeIs('map.public') ? 'text-amber-600' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-.553-.894L15 7m0 13V7"/>
                    </svg>
                    <span>{{ __('Carte') }}</span>
                </a>
                
                <a href="/" 
                         class="flex flex-col items-center justify-center py-2 px-3 text-xs font-semibold transition btn-touch interactive-lift
                   {{ request()->is('/') ? 'text-amber-600' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>{{ __('Accueil') }}</span>
                </a>
            </div>
        </div>
    </nav>
    
    @livewireScripts
    @stack('scripts')
</body>
</html>
