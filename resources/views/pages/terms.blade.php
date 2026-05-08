@extends('layouts.citizen')

@section('title', app()->getLocale() === 'fr' ? 'Conditions d\'utilisation' : 'Terms of Service')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    <div class="citizen-card p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ app()->getLocale() === 'fr' ? 'Conditions d\'utilisation' : 'Terms of Service' }}
        </h1>
        <p class="text-sm text-gray-700">
            {{ app()->getLocale() === 'fr'
                ? 'En utilisant NidVite, vous confirmez soumettre des informations exactes et pertinentes pour le traitement municipal des nids-de-poule.'
                : 'By using NidVite, you agree to provide accurate information relevant to municipal pothole handling.' }}
        </p>
        <h2 class="text-lg font-semibold text-gray-900">{{ app()->getLocale() === 'fr' ? 'Usage acceptable' : 'Acceptable use' }}</h2>
        <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
            <li>{{ app()->getLocale() === 'fr' ? 'Ne pas soumettre de contenu frauduleux ou abusif' : 'Do not submit fraudulent or abusive content' }}</li>
            <li>{{ app()->getLocale() === 'fr' ? 'Ne pas televerser de contenu illicite' : 'Do not upload illegal content' }}</li>
            <li>{{ app()->getLocale() === 'fr' ? 'Utiliser la plateforme pour des signalements lies a Montreal' : 'Use the platform for Montreal-related reports' }}</li>
        </ul>
        <h2 class="text-lg font-semibold text-gray-900">{{ app()->getLocale() === 'fr' ? 'Limitation' : 'Limitation' }}</h2>
        <p class="text-sm text-gray-700">
            {{ app()->getLocale() === 'fr'
                ? 'NidVite ne garantit pas les delais de reparation et peut suspendre les comptes abusifs ou les signalements non conformes.'
                : 'NidVite does not guarantee repair timelines and may suspend abusive submissions.' }}
        </p>
    </div>
</div>
@endsection
