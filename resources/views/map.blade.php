@extends('layouts.citizen')

@section('title', __('Carte des signalements') . ' - ' . config('app.name'))

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #map { height: calc(100vh - 8rem); width: 100%; border-radius: 0.75rem; }
    .report-popup {
        min-width: 200px;
        font-family: 'Inter', system-ui, sans-serif;
    }
    .report-popup h3 {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .report-popup p {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 8px;
        line-height: 1.4;
    }
    .report-popup .status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-bottom: 8px;
    }
    .report-popup .status-received { background: #fef3c7; color: #92400e; }
    .report-popup .status-verified { background: #dbeafe; color: #1e40af; }
    .report-popup .status-scheduled { background: #e0e7ff; color: #3730a3; }
    .report-popup .status-in_progress { background: #fce7f3; color: #9d174d; }
    .report-popup .status-repaired { background: #d1fae5; color: #065f46; }
    .report-popup .status-rejected { background: #fee2e2; color: #991b1b; }
    .report-popup a {
        display: inline-block;
        padding: 6px 12px;
        background: #d97706;
        color: white;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 500;
        text-decoration: none;
        transition: background 0.2s;
    }
    .report-popup a:hover {
        background: #b45309;
    }
    @media (max-width: 640px) {
        #map { height: calc(100vh - 7rem); }
    }
</style>
@endpush

@section('content')
{{-- Back button --}}
<div class="max-w-3xl mx-auto px-4 pt-3 pb-1">
    <a href="/" class="inline-flex items-center text-sm text-gray-500 hover:text-amber-600 transition btn-touch py-1">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        {{ app()->getLocale() === 'fr' ? 'Retour' : 'Back' }}
    </a>
</div>

<div class="px-4 py-2 h-full">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h1 class="text-lg font-bold text-gray-900">{{ __('map.title') }}</h1>
            <p class="text-xs text-gray-500">{{ __('map.subtitle') }}</p>
        </div>
        <a href="{{ route('report.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-xl hover:bg-amber-700 active:scale-[0.98] transition-all btn-touch">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('map.report') }}
        </a>
    </div>
    
    <div id="map"></div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    // Initialize map centered on Montreal
    const map = L.map('map').setView([45.5017, -73.5673], 12);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    // Status colors for markers
    const statusColors = {
        received: '#d97706',
        verified: '#3b82f6',
        scheduled: '#6366f1',
        in_progress: '#db2777',
        repaired: '#10b981',
        rejected: '#ef4444',
    };

    // Fetch reports
    fetch('{{ route('api.reports.geojson') }}')
        .then(response => response.json())
        .then(data => {
            const bounds = L.latLngBounds();

            data.features.forEach(feature => {
                const coords = feature.geometry.coordinates;
                const props = feature.properties;
                const color = statusColors[props.status] || '#6b7280';

                const marker = L.circleMarker([coords[1], coords[0]], {
                    radius: 8,
                    fillColor: color,
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.85,
                }).addTo(map);

                const popupContent = `
                    <div class="report-popup">
                        <span class="status status-${props.status}">${props.status_label}</span>
                        <h3>${props.address || '{{ __('map.no_address') }}'}</h3>
                        <p>${props.neighborhood || ''}</p>
                        <p>${props.description ? props.description.substring(0, 100) + (props.description.length > 100 ? '...' : '') : ''}</p>
                        <a href="${props.url}" target="_blank">{{ __('map.view_details') }}</a>
                    </div>
                `;

                marker.bindPopup(popupContent);
                bounds.extend([coords[1], coords[0]]);
            });

            // Fit map to show all markers if there are any
            if (data.features.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        })
        .catch(error => {
            console.error('Error loading reports:', error);
        });
</script>
@endpush
@endsection
