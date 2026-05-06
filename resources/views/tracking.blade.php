<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#D97706">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="NidVite">
    <title>{{ __('Suivi de signalement') }} - {{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    @laravelPWA
    <script src="https://cdn.tailwindcss.com"></script>
    @if($location)
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endif
    <style>
        #tracking-map { height: 250px; border-radius: 0.75rem; }
    </style>
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <header class="bg-amber-600 text-white py-4 px-6 shadow">
            <div class="max-w-xl mx-auto flex items-center justify-between">
                <h1 class="text-lg font-bold">{{ config('app.name') }}</h1>
                <span class="text-sm opacity-90">{{ __('Suivi') }}</span>
            </div>
        </header>

        <main class="flex-1 max-w-xl mx-auto w-full px-6 py-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">{{ __('Votre signalement') }}</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @switch($report->status)
                            @case('received') bg-gray-100 text-gray-800 @break
                            @case('verified') bg-blue-100 text-blue-800 @break
                            @case('scheduled') bg-yellow-100 text-yellow-800 @break
                            @case('in_progress') bg-orange-100 text-orange-800 @break
                            @case('repaired') bg-green-100 text-green-800 @break
                            @case('rejected') bg-red-100 text-red-800 @break
                            @default bg-gray-100 text-gray-800
                        @endswitch">
                        {{ __("status.{$report->status}") }}
                    </span>
                </div>

                <p class="text-sm text-gray-500 mb-2">{{ __('Numéro') }}: <span class="font-mono text-gray-700">{{ $report->uuid }}</span></p>
                <p class="text-sm text-gray-500 mb-2">{{ __('Date') }}: {{ $report->created_at->translatedFormat('j F Y') }}</p>
                @if($report->category)
                    <p class="text-sm text-gray-500 mb-4">{{ __('Catégorie') }}: {{ $report->category->label_fr }}</p>
                @endif

                @if($report->address)
                    <p class="text-sm text-gray-700 mb-4"><span class="font-medium">{{ __('Adresse') }}:</span> {{ $report->address }}</p>
                @endif

                @if($report->description)
                    <p class="text-sm text-gray-700 mb-4"><span class="font-medium">{{ __('Description') }}:</span> {{ $report->description }}</p>
                @endif

                @if($location)
                    <div id="tracking-map" class="mt-4 border border-gray-200"></div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('Historique') }}</h3>

                <div class="space-y-0">
                    @php
                        $steps = [
                            ['status' => 'received', 'label' => __('Reçu'), 'icon' => '📥'],
                            ['status' => 'verified', 'label' => __('Vérifié'), 'icon' => '🔍'],
                            ['status' => 'scheduled', 'label' => __('Planifié'), 'icon' => '📅'],
                            ['status' => 'in_progress', 'label' => __('En cours'), 'icon' => '🚧'],
                            ['status' => 'repaired', 'label' => __('Réparé'), 'icon' => '✅'],
                        ];
                        $currentIndex = array_search($report->status, array_column($steps, 'status'));
                        if ($currentIndex === false && $report->status === 'rejected') {
                            $currentIndex = -1;
                        }
                    @endphp

                    @if($report->status === 'rejected')
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-red-50 border border-red-200">
                            <span class="text-xl">❌</span>
                            <div>
                                <p class="font-medium text-red-900">{{ __('Signalement rejeté') }}</p>
                                @if($report->rejection_reason)
                                    <p class="text-sm text-red-700 mt-1">{{ $report->rejection_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        @foreach($steps as $index => $step)
                            <div class="flex items-start gap-3 p-3 rounded-lg
                                {{ $index <= $currentIndex ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50 border border-gray-100 opacity-60' }}">
                                <span class="text-xl">{{ $step['icon'] }}</span>
                                <div>
                                    <p class="font-medium {{ $index <= $currentIndex ? 'text-amber-900' : 'text-gray-500' }}">
                                        {{ $step['label'] }}
                                    </p>
                                    @if($index === $currentIndex)
                                        <p class="text-xs text-amber-700 mt-0.5">{{ __('Statut actuel') }}</p>
                                    @endif
                                </div>
                            </div>
                            @if(!$loop->last)
                                <div class="ml-6 h-4 w-px {{ $index < $currentIndex ? 'bg-amber-300' : 'bg-gray-200' }}"></div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('report.create') }}" class="text-sm text-amber-600 hover:text-amber-700 font-medium">
                    ← {{ __('Faire un nouveau signalement') }}
                </a>
            </div>
        </main>
    </div>

    @if($location)
    <script>
        const map = L.map('tracking-map').setView([{{ $location->lat }}, {{ $location->lng }}], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);
        L.marker([{{ $location->lat }}, {{ $location->lng }}]).addTo(map);
    </script>
    @endif
</body>
</html>
