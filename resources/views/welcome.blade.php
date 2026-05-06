@extends('layouts.citizen')

@section('title', config('app.name'))

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #welcome-map { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 0; }
    .map-overlay { position: relative; z-index: 10; pointer-events: none; }
    .map-overlay > * { pointer-events: auto; }
</style>
@endpush

@section('content')
{{-- Full-screen map --}}
<div class="relative w-full" style="height: 80vh;">
    <div id="welcome-map"></div>

    {{-- Overlay buttons --}}
    <div class="map-overlay absolute bottom-0 left-0 right-0 p-4 pb-6">
        <div class="max-w-sm mx-auto space-y-3">
            {{-- Report CTA --}}
            <a href="{{ route('report.create') }}"
               class="w-full inline-flex items-center justify-center px-6 py-4 text-lg font-semibold rounded-2xl shadow-xl text-white bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 active:scale-[0.98] transition-all duration-200 btn-touch">
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
                        class="w-full inline-flex items-center justify-center px-6 py-4 text-lg font-medium rounded-2xl border-2 border-white/80 text-white bg-black/40 backdrop-blur-sm hover:bg-black/50 active:scale-[0.98] transition-all duration-200 btn-touch">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    {{ app()->getLocale() === 'fr' ? 'Suivre un signalement' : 'Follow up on an issue' }}
                </button>

                <div x-show="showInput" x-cloak class="space-y-3 animate-fade-in">
                    <div class="relative">
                        <input type="text" x-model="trackingId"
                               class="block w-full rounded-2xl border-0 shadow-xl text-base transition px-4 py-4 pr-24 uppercase tracking-wide"
                               placeholder="{{ app()->getLocale() === 'fr' ? 'Numéro de signalement' : 'Report ID' }}"
                               x-on:keydown.enter="if(trackingId.trim()) window.location.href = '/suivi/' + trackingId.trim()">
                        <button type="button"
                                x-on:click="if(trackingId.trim()) window.location.href = '/suivi/' + trackingId.trim()"
                                class="absolute right-2 top-2 bottom-2 px-5 bg-amber-600 text-white text-sm font-semibold rounded-xl hover:bg-amber-700 active:scale-[0.98] transition-all btn-touch">
                                {{ app()->getLocale() === 'fr' ? 'OK' : 'Go' }}
                        </button>
                    </div>
                    <button type="button" x-on:click="showInput = false; trackingId = ''"
                            class="w-full text-sm text-white/90 hover:text-white py-2 btn-touch">
                        {{ app()->getLocale() === 'fr' ? 'Annuler' : 'Cancel' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const map = L.map('welcome-map', { zoomControl: false }).setView([45.5017, -73.5673], 12);
    L.control.zoom({ position: 'topright' }).addTo(map);

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
                    radius: 7,
                    fillColor: color,
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9,
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
