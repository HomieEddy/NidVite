@extends('layouts.citizen')

@section('title', app()->getLocale() === 'fr' ? 'Politique de confidentialite' : 'Privacy Policy')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    <div class="citizen-card p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ app()->getLocale() === 'fr' ? 'Politique de confidentialite' : 'Privacy Policy' }}
        </h1>
        <p class="text-sm text-gray-700">
            {{ app()->getLocale() === 'fr'
                ? 'NidVite collecte uniquement les donnees necessaires pour traiter un signalement de nid-de-poule, informer le citoyen de l\'evolution, et maintenir la securite operationnelle.'
                : 'NidVite only collects data needed to process pothole reports, inform citizens about progress, and maintain operational security.' }}
        </p>
        <h2 class="text-lg font-semibold text-gray-900">{{ app()->getLocale() === 'fr' ? 'Donnees collectees' : 'Data collected' }}</h2>
        <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
            <li>{{ app()->getLocale() === 'fr' ? 'Coordonnees du signalement (adresse, geolocalisation, photos)' : 'Report details (address, geolocation, photos)' }}</li>
            <li>{{ app()->getLocale() === 'fr' ? 'Adresse courriel du declarant' : 'Reporter email address' }}</li>
            <li>{{ app()->getLocale() === 'fr' ? 'Metadonnees anti-abus et de securite' : 'Anti-abuse and security metadata' }}</li>
        </ul>
        <h2 class="text-lg font-semibold text-gray-900">{{ app()->getLocale() === 'fr' ? 'Conservation' : 'Retention' }}</h2>
        <p class="text-sm text-gray-700">
            {{ app()->getLocale() === 'fr'
                ? 'Les donnees sont conservees selon la politique de retention documentee pour respecter la Loi 25 du Quebec.'
                : 'Data is retained according to documented retention policy to align with Quebec Law 25.' }}
        </p>
    </div>
</div>
@endsection
