@extends('layouts.citizen')

@section('title', config('app.name'))

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #welcome-map { position: absolute; inset: 0; width: 100%; height: 100%; border-radius: 1rem; z-index: 0; }
    .map-container { position: relative; height: calc(100vh - 8rem); min-height: 300px; padding: 0.5rem; }
</style>
@endpush

@section('content')
@php $errorMsg = app()->getLocale() === 'fr' ? 'Signalement non trouvé' : 'Report not found'; @endphp
<script>
    function tracker() {
        return {
            showInput: false,
            trackingId: '',
            error: '',
            errorMsg: @json($errorMsg),
            modalOpen: false,
            loading: false,
            report: null,
            lookup() {
                this.error = '';
                var id = this.trackingId.trim();
                if (!id) return;
                this.showInput = false;
                this.modalOpen = true;
                this.loading = true;
                this.report = null;
                fetch('/api/reports/' + id + '/lookup')
                    .then(function(r) {
                        if (!r.ok) throw new Error('not_found');
                        return r.json();
                    })
                    .then(function(data) {
                        this.report = data;
                        this.loading = false;
                    }.bind(this))
                    .catch(function() {
                        this.loading = false;
                        this.modalOpen = false;
                        this.error = this.errorMsg;
                        this.showInput = true;
                    }.bind(this));
            }
        }
    }
</script>
<div x-data="tracker()" class="flex flex-col h-full">
    {{-- Map with border padding --}}
    <div class="map-container">
        <div id="welcome-map"></div>

        {{-- Overlay buttons --}}
        <div class="absolute bottom-6 left-4 right-4 z-10">
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
                <button type="button"
                        x-show="!showInput"
                        x-on:click="showInput = true"
                        class="w-full inline-flex items-center justify-center px-6 py-4 text-lg font-medium rounded-2xl shadow-xl text-gray-900 bg-white hover:bg-gray-50 active:scale-[0.98] transition-all duration-200 btn-touch">
                    <svg class="w-6 h-6 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    {{ app()->getLocale() === 'fr' ? 'Suivre un signalement' : 'Follow up on an issue' }}
                </button>

                {{-- Track input --}}
                <div x-show="showInput" x-cloak class="space-y-3 animate-fade-in">
                    <div class="bg-white rounded-2xl shadow-xl p-4 space-y-3">
                        <div class="relative">
                            <input type="text" x-model="trackingId"
                                   class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3.5 pr-20 uppercase tracking-wide"
                                   placeholder="{{ app()->getLocale() === 'fr' ? 'Numéro de signalement' : 'Report ID' }}"
                                   x-on:keydown.enter="lookup()">
                            <button type="button"
                                    x-on:click="lookup()"
                                    class="absolute right-2 top-2 bottom-2 px-4 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 active:scale-[0.98] transition-all btn-touch">
                                {{ app()->getLocale() === 'fr' ? 'OK' : 'Go' }}
                            </button>
                        </div>
                        <p x-show="error" x-text="error" class="text-sm text-red-600"></p>
                    </div>
                    <button type="button" x-on:click="showInput = false; trackingId = ''; error = ''"
                            class="w-full text-sm text-white font-medium py-2 btn-touch drop-shadow-md">
                        {{ app()->getLocale() === 'fr' ? 'Annuler' : 'Cancel' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tracking Modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-transition.opacity>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-on:click="modalOpen = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md max-h-[85vh] overflow-y-auto animate-slide-up" x-on:click.away="modalOpen = false">
            {{-- Header --}}
            <div class="sticky top-0 bg-white border-b border-gray-100 px-5 py-4 flex items-center justify-between rounded-t-3xl z-10">
                <h2 class="text-lg font-bold text-gray-900">{{ app()->getLocale() === 'fr' ? 'Votre signalement' : 'Your report' }}</h2>
                <button type="button" x-on:click="modalOpen = false" class="p-2 rounded-full hover:bg-gray-100 transition btn-touch">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-5">
                {{-- Loading --}}
                <div x-show="loading" class="py-12 text-center">
                    <svg class="animate-spin mx-auto h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-3 text-sm text-gray-500">{{ app()->getLocale() === 'fr' ? 'Chargement...' : 'Loading...' }}</p>
                </div>

                <template x-if="report && !loading">
                    <div class="space-y-5">
                        {{-- Status badge --}}
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded" x-text="report.uuid"></span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-gray-100 text-gray-800': report.status === 'received',
                                      'bg-blue-100 text-blue-800': report.status === 'verified',
                                      'bg-yellow-100 text-yellow-800': report.status === 'scheduled',
                                      'bg-orange-100 text-orange-800': report.status === 'in_progress',
                                      'bg-green-100 text-green-800': report.status === 'repaired',
                                      'bg-red-100 text-red-800': report.status === 'rejected',
                                  }"
                                  x-text="report.status_label"></span>
                        </div>

                        {{-- Info --}}
                        <div class="space-y-2 text-sm">
                            <div x-show="report.category" class="flex">
                                <span class="text-gray-500 w-24 flex-shrink-0">{{ app()->getLocale() === 'fr' ? 'Type' : 'Type' }}</span>
                                <span class="text-gray-900" x-text="report.category"></span>
                            </div>
                            <div x-show="report.address" class="flex">
                                <span class="text-gray-500 w-24 flex-shrink-0">{{ app()->getLocale() === 'fr' ? 'Adresse' : 'Address' }}</span>
                                <span class="text-gray-900" x-text="report.address"></span>
                            </div>
                            <div x-show="report.created_at" class="flex">
                                <span class="text-gray-500 w-24 flex-shrink-0">{{ app()->getLocale() === 'fr' ? 'Date' : 'Date' }}</span>
                                <span class="text-gray-900" x-text="new Date(report.created_at).toLocaleDateString()"></span>
                            </div>
                        </div>

                        {{-- Rejected --}}
                        <div x-show="report.status === 'rejected'" class="p-4 rounded-xl bg-red-50 border border-red-200">
                            <p class="font-semibold text-red-900 text-sm">{{ app()->getLocale() === 'fr' ? 'Signalement rejeté' : 'Report rejected' }}</p>
                            <p x-show="report.rejection_reason" x-text="report.rejection_reason" class="text-sm text-red-700 mt-1"></p>
                        </div>

                        {{-- Progress meter --}}
                        <div x-show="report.status !== 'rejected'">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-700">{{ app()->getLocale() === 'fr' ? 'Progression' : 'Progress' }}</span>
                                <span class="text-sm font-bold text-amber-600" x-text="Math.round(report.progress.percent) + '%'"></span>
                            </div>

                            {{-- Bar --}}
                            <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-500 to-amber-600 rounded-full transition-all duration-700 ease-out"
                                     :style="'width: ' + report.progress.percent + '%'"></div>
                            </div>

                            {{-- Steps --}}
                            <div class="mt-4 space-y-2">
                                <template x-for="(step, idx) in report.steps" :key="step.status">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold"
                                             :class="step.done ? (step.current ? 'bg-amber-600 text-white' : 'bg-amber-200 text-amber-800') : 'bg-gray-200 text-gray-400'">
                                            <template x-if="step.done && !step.current">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                            <template x-if="step.current">
                                                <span class="w-2 h-2 bg-white rounded-full"></span>
                                            </template>
                                            <template x-if="!step.done">
                                                <span x-text="idx + 1"></span>
                                            </template>
                                        </div>
                                        <span class="text-sm font-medium"
                                              :class="step.done ? (step.current ? 'text-amber-900' : 'text-gray-700') : 'text-gray-400'"
                                              x-text="step.label"></span>
                                        <span x-show="step.current" class="text-xs text-amber-600 font-semibold ml-auto">{{ app()->getLocale() === 'fr' ? 'En cours' : 'Current' }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- View full page link --}}
                        <a :href="'/suivi/' + report.uuid" target="_blank"
                           class="block w-full text-center py-3 text-sm font-medium text-amber-600 hover:text-amber-700 bg-amber-50 rounded-xl hover:bg-amber-100 transition btn-touch">
                            {{ app()->getLocale() === 'fr' ? 'Voir la page complète' : 'View full page' }} →
                        </a>
                    </div>
                </template>
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
