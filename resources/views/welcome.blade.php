<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#D97706">
    <title>{{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    @laravelPWA
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <header class="bg-amber-600 text-white py-4 px-6 shadow">
            <div class="max-w-3xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-xl font-bold">{{ config('app.name') }}</span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('map.public') }}" class="text-sm font-medium text-amber-100 hover:text-white transition">
                        {{ __('map.title') }}
                    </a>
                    <div class="flex items-center space-x-1">
                        <a href="{{ route('locale.switch', 'fr') }}" class="px-2 py-1 rounded text-sm font-medium {{ app()->getLocale() === 'fr' ? 'bg-white text-amber-600' : 'text-amber-100 hover:text-white' }}">FR</a>
                        <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded text-sm font-medium {{ app()->getLocale() === 'en' ? 'bg-white text-amber-600' : 'text-amber-100 hover:text-white' }}">EN</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="max-w-xl w-full text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-amber-100 mb-6">
                    <svg class="h-10 w-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ config('app.name') }}</h1>
                <p class="text-lg text-gray-600 mb-8">
                    {{ app()->getLocale() === 'fr' ? 'Ameliorons Montreal ensemble en signalant les nids-de-poule et autres problemes de voirie.' : 'Let\'s improve Montreal together by reporting potholes and other road issues.' }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('report.create') }}" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-amber-600 hover:bg-amber-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('report.title') }}
                    </a>
                    <a href="{{ route('map.public') }}" class="inline-flex items-center justify-center px-8 py-4 border border-gray-300 text-base font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-.553-.894L15 7m0 13V7"/>
                        </svg>
                        {{ __('map.title') }}
                    </a>
                </div>
            </div>
        </main>

        <footer class="bg-white border-t">
            <div class="max-w-3xl mx-auto px-4 py-6 text-center">
                <p class="text-sm text-gray-500">
                    {{ config('app.name') }} - {{ app()->getLocale() === 'fr' ? 'Ameliorons Montreal ensemble' : 'Improving Montreal together' }}
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
