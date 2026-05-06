@extends('layouts.citizen')

@section('title', __('tracking.Suivi de signalement') . ' - ' . config('app.name'))

@push('styles')
@if($location)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #tracking-map { height: 200px; border-radius: 0.75rem; }
</style>
@endif
@endpush

@section('content')
{{-- Back button --}}
<div class="max-w-3xl mx-auto px-4 pt-3 pb-1">
    <a href="/" class="inline-flex items-center text-sm text-gray-500 hover:text-amber-600 transition btn-touch py-1">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        {{ __('map.back_home') }}
    </a>
</div>

<div class="max-w-3xl mx-auto px-4 py-4">
    {{-- Report Card --}}
    <div class="citizen-card p-5 mb-4 animate-fade-in">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900">{{ __('tracking.Votre signalement') }}</h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                @switch($report->status)
                    @case('received') bg-gray-100 text-gray-800 @break
                    @case('verified') bg-blue-100 text-blue-800 @break
                    @case('scheduled') bg-yellow-100 text-yellow-800 @break
                    @case('in_progress') bg-orange-100 text-orange-800 @break
                    @case('repaired') bg-green-100 text-green-800 @break
                    @case('rejected') bg-red-100 text-red-800 @break
                    @default bg-gray-100 text-gray-800
                @endswitch">
                {{ __("report.status.{$report->status}") }}
            </span>
        </div>

        <div class="space-y-2.5">
            <div class="flex items-center text-sm">
                <span class="text-gray-500 w-20 flex-shrink-0">{{ __('tracking.Numéro') }}</span>
                <span class="font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $report->uuid }}</span>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-gray-500 w-20 flex-shrink-0">{{ __('tracking.Date') }}</span>
                <span class="text-gray-700">{{ $report->created_at->translatedFormat('j F Y') }}</span>
            </div>
            @if($report->category)
                <div class="flex items-center text-sm">
                    <span class="text-gray-500 w-20 flex-shrink-0">{{ __('tracking.Catégorie') }}</span>
                    <span class="text-gray-700">{{ app()->getLocale() === 'fr' ? $report->category->label_fr : $report->category->label_en }}</span>
                </div>
            @endif
            @if($report->address)
                <div class="flex items-start text-sm">
                    <span class="text-gray-500 w-20 flex-shrink-0">{{ __('tracking.Adresse') }}</span>
                    <span class="text-gray-700">{{ $report->address }}</span>
                </div>
            @endif
        </div>

        @if($location)
            <div id="tracking-map" class="mt-4 border border-gray-200"></div>
        @endif
    </div>

    {{-- Timeline --}}
    <div class="citizen-card p-5 animate-slide-up">
        <h3 class="text-base font-bold text-gray-900 mb-4">{{ __('tracking.Historique') }}</h3>

        <div class="space-y-0">
            @php
                $steps = [
                    ['status' => 'received', 'label' => __('report.status.received'), 'icon' => '📥'],
                    ['status' => 'verified', 'label' => __('report.status.verified'), 'icon' => '🔍'],
                    ['status' => 'scheduled', 'label' => __('report.status.scheduled'), 'icon' => '📅'],
                    ['status' => 'in_progress', 'label' => __('report.status.in_progress'), 'icon' => '🚧'],
                    ['status' => 'repaired', 'label' => __('report.status.repaired'), 'icon' => '✅'],
                ];
                $currentIndex = array_search($report->status, array_column($steps, 'status'));
                if ($currentIndex === false && $report->status === 'rejected') {
                    $currentIndex = -1;
                }
            @endphp

            @if($report->status === 'rejected')
                <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200">
                    <span class="text-xl flex-shrink-0">❌</span>
                    <div>
                        <p class="font-semibold text-red-900">{{ __('tracking.Signalement rejeté') }}</p>
                        @if($report->rejection_reason)
                            <p class="text-sm text-red-700 mt-1">{{ $report->rejection_reason }}</p>
                        @endif
                    </div>
                </div>
            @else
                @foreach($steps as $index => $step)
                    <div class="flex items-start gap-3 p-3.5 rounded-xl
                        {{ $index <= $currentIndex ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50 border border-gray-100 opacity-60' }}">
                        <span class="text-xl flex-shrink-0">{{ $step['icon'] }}</span>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm {{ $index <= $currentIndex ? 'text-amber-900' : 'text-gray-500' }}">
                                {{ $step['label'] }}
                            </p>
                            @if($index === $currentIndex)
                                <p class="text-xs text-amber-700 mt-0.5">{{ __('tracking.Statut actuel') }}</p>
                            @endif
                        </div>
                    </div>
                    @if(!$loop->last)
                        <div class="ml-7 h-4 w-px {{ $index < $currentIndex ? 'bg-amber-300' : 'bg-gray-200' }}"></div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-6 text-center pb-4">
        <a href="{{ route('report.create') }}" class="inline-flex items-center text-sm text-amber-600 hover:text-amber-700 font-medium btn-touch py-2 px-4">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('tracking.Faire un nouveau signalement') }}
        </a>
    </div>
</div>

@push('scripts')
@if($location)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const map = L.map('tracking-map').setView([{{ $location->lat }}, {{ $location->lng }}], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);
    L.marker([{{ $location->lat }}, {{ $location->lng }}]).addTo(map);
</script>
@endif
@endpush
@endsection
