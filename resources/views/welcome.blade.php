@extends('layouts.citizen')

@section('title', config('app.name'))

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #welcome-map { height: 280px; width: 100%; border-radius: 1rem; }
    @media (min-width: 640px) { #welcome-map { height: 320px; } }
</style>
@endpush

@section('content')
<div class="flex flex-col">
    {{-- Map --}}
    <div class="px-4 pt-4 pb-2">
        <div id="welcome-map" class="shadow-md border border-gray-200"></div>
    </div>

    {{-- Actions --}}
    <div class="px-4 py-4 space-y-3">
        {{-- Report CTA --}}
        <a href="{{ route('report.create') }}"
           class="w-full inline-flex items-center justify-center px-6 py-4 text-lg font-semibold rounded-2xl shadow-lg text-white bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 active:scale-[0.98] transition-all duration-200 btn-touch">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ app()->getLocale() === 'fr' ? 'Faire un signalement' : 'Report an issue' }}
        </a>

        {{-- Track CTA --}}
        <div x-data="{ trackingId: '', showInput: false }" class="w-full">
            <button type="button"
                    x-show="!showInput"
                    x-on:click="showInput = true"
                    class="w-full inline-flex items-center justify-center px-6 py-4 text-lg font-medium rounded-2xl border-2 border-gray-200 text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 active:scale-[0.98] transition-all duration-200 btn-touch">
                <svg class="w-6 h-6 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                {{ app()->getLocale() === 'fr' ? 'Suivre un signalement' : 'Follow up on an issue' }}
            </button>

            <div x-show="showInput" x-cloak class="space-y-3 animate-fade-in">
                <div class="relative">
                    <input type="text" x-model="trackingId"
                           class="block w-full rounded-2xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-4 pr-24 uppercase tracking-wide"
                           placeholder="{{ app()->getLocale() === 'fr' ? 'Numéro de signalement' : 'Report ID' }}"
                           x-on:keydown.enter="if(trackingId.trim()) window.location.href = '/suivi/' + trackingId.trim()">
                    <button type="button"
                            x-on:click="if(trackingId.trim()) window.location.href = '/suivi/' + trackingId.trim()"
                            class="absolute right-2 top-2 bottom-2 px-4 bg-amber-600 text-white text-sm font-semibold rounded-xl hover:bg-amber-700 active:scale-[0.98] transition-all btn-touch">
                        {{ app()->getLocale() === 'fr' ? 'OK' : 'Go' }}
                    </button>
                </div>
                <button type="button" x-on:click="showInput = false; trackingId = ''"
                        class="w-full text-sm text-gray-500 hover:text-gray-700 py-2 btn-touch">
                    {{ app()->getLocale() === 'fr' ? 'Annuler' : 'Cancel' }}
                </button>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="mt-auto py-4 text-center">
        <p class="text-xs text-gray-400">
            {{ config('app.name') }} - {{ app()->getLocale() === 'fr' ? 'Montréal' : 'Montreal' }} 2026
        </p>
    </footer>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const map = L.map('welcome-map').setView([45.5017, -73.5673], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const statusColors = {
        received: '#d97706',
        verified: '#3b82f6',
        scheduled: '#6366f1',
        in_progress: '#db2777',
        repaired: '#10b981',
        rejected: '#ef4444',
    };

    fetch('{{ route('api.reports.geojson') }}')
        .then(r => r.json())
        .then(data => {
            const bounds = L.latLngBounds();
            data.features.forEach(feature => {
                const coords = feature.geometry.coordinates;
                const props = feature.properties;
                const color = statusColors[props.status] || '#6b7280';
                const marker = L.circleMarker([coords[1], coords[0]], {
                    radius: 6,
                    fillColor: color,
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.85,
                }).addTo(map);
                marker.bindPopup(`<div style="font-family:system-ui,sans-serif;font-size:0.8rem"><strong>${props.address || ''}</strong><br><span style="color:${color};font-weight:600">${props.status_label}</span></div>`);
                bounds.extend([coords[1], coords[0]]);
            });
            if (data.features.length > 0) {
                map.fitBounds(bounds, { padding: [40, 40] });
            }
        })
        .catch(err => console.error('Error loading reports:', err));
</script>
@endpush
