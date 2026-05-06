@extends('layouts.citizen')

@section('title', config('app.name'))

@section('content')
<div class="min-h-[calc(100vh-8rem)] flex flex-col">
    {{-- Hero Section --}}
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-8 text-center animate-fade-in">
        {{-- App Icon / Logo --}}
        <div class="relative mb-8">
            <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-amber-500 to-amber-600 shadow-xl flex items-center justify-center mx-auto">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            {{-- Floating notification badge --}}
            <div class="absolute -top-1 -right-1 w-8 h-8 bg-red-500 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
        </div>
        
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3 tracking-tight">
            {{ config('app.name') }}
        </h1>
        
        <p class="text-lg text-gray-600 mb-2 max-w-sm mx-auto leading-relaxed">
            {{ app()->getLocale() === 'fr' 
                ? 'Améliorons Montréal ensemble' 
                : 'Let\'s improve Montreal together' }}
        </p>
        
        <p class="text-sm text-gray-500 mb-10 max-w-xs mx-auto">
            {{ app()->getLocale() === 'fr' 
                ? 'Signalez les nids-de-poule et problèmes de voirie en quelques secondes' 
                : 'Report potholes and road issues in seconds' }}
        </p>
        
        {{-- Primary CTA --}}
        <a href="{{ route('report.create') }}" 
           class="w-full max-w-xs inline-flex items-center justify-center px-8 py-4 text-lg font-semibold rounded-2xl shadow-lg text-white bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 active:scale-[0.98] transition-all duration-200 btn-touch">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ app()->getLocale() === 'fr' ? 'Faire un signalement' : 'Report an issue' }}
        </a>
        
        {{-- Secondary CTA --}}
        <a href="{{ route('map.public') }}" 
           class="mt-3 w-full max-w-xs inline-flex items-center justify-center px-8 py-4 text-base font-medium rounded-2xl border-2 border-gray-200 text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 active:scale-[0.98] transition-all duration-200 btn-touch">
            <svg class="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-.553-.894L15 7m0 13V7"/>
            </svg>
            {{ app()->getLocale() === 'fr' ? 'Voir la carte' : 'View map' }}
        </a>
    </div>
    
    {{-- Stats / Trust Section --}}
    <div class="px-6 pb-6">
        <div class="citizen-card p-5 max-w-sm mx-auto animate-slide-up">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-amber-600">3</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ app()->getLocale() === 'fr' ? 'clics' : 'clicks' }}</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-amber-600">30s</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ app()->getLocale() === 'fr' ? 'pour signaler' : 'to report' }}</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-amber-600">100%</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ app()->getLocale() === 'fr' ? 'gratuit' : 'free' }}</div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- How it works --}}
    <div class="px-6 pb-8">
        <h2 class="text-center text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">
            {{ app()->getLocale() === 'fr' ? 'Comment ça marche' : 'How it works' }}
        </h2>
        
        <div class="space-y-3 max-w-sm mx-auto">
            <div class="flex items-center space-x-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-amber-700 font-bold text-sm">1</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 text-sm">{{ app()->getLocale() === 'fr' ? 'Prenez une photo' : 'Take a photo' }}</p>
                    <p class="text-xs text-gray-500">{{ app()->getLocale() === 'fr' ? 'Du nid-de-poule ou du problème' : 'Of the pothole or issue' }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-amber-700 font-bold text-sm">2</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 text-sm">{{ app()->getLocale() === 'fr' ? 'Partagez la localisation' : 'Share location' }}</p>
                    <p class="text-xs text-gray-500">{{ app()->getLocale() === 'fr' ? 'GPS automatique en un clic' : 'Automatic GPS in one click' }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-amber-700 font-bold text-sm">3</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 text-sm">{{ app()->getLocale() === 'fr' ? 'Suivez les réparations' : 'Track repairs' }}</p>
                    <p class="text-xs text-gray-500">{{ app()->getLocale() === 'fr' ? 'Notifications par email' : 'Email notifications' }}</p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Footer --}}
    <footer class="mt-auto py-6 text-center">
        <p class="text-xs text-gray-400">
            {{ config('app.name') }} - {{ app()->getLocale() === 'fr' ? 'Montréal' : 'Montreal' }} 2026
        </p>
    </footer>
</div>
@endsection
