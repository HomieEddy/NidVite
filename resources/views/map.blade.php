@extends('layouts.citizen')

@section('title', __('map.title') . ' - ' . config('app.name'))

@push('styles')
<style>
    .map-page-container { display: flex; flex-direction: column; height: calc(100dvh - 8rem); overflow: hidden; }
    #map { flex: 1; min-height: 0; width: 100%; border-radius: 0.75rem; overflow: hidden; }
    .report-popup {
        min-width: 220px;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    }
    .report-popup h3 {
        font-size: 0.9rem;
        font-weight: 700;
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
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        margin-bottom: 8px;
    }
    .report-popup .status-received { background: #dbeafe; color: #1e40af; }
    .report-popup .status-verified { background: #dbeafe; color: #1e40af; }
    .report-popup .status-planned { background: #fef9c3; color: #854d0e; }
    .report-popup .status-scheduled { background: #fef9c3; color: #854d0e; }
    .report-popup .status-in_progress { background: #fef9c3; color: #854d0e; }
    .report-popup .status-repaired { background: #d1fae5; color: #065f46; }
    .report-popup a {
        display: inline-block;
        padding: 8px 12px;
        background: linear-gradient(90deg, #b45309, #d97706);
        color: white;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s, opacity 0.2s;
    }
    .report-popup a:hover {
        opacity: 0.95;
        transform: translateY(-1px);
    }
</style>
@endpush

@section('content')
@include('components.dummy-data-notice')

<div class="map-page-container px-4 py-2">
    {{-- Back button + header --}}
    <div class="flex items-center justify-between mb-2 shrink-0">
        <a href="/" class="inline-flex items-center text-sm text-gray-500 hover:text-amber-600 transition btn-touch py-1">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('map.back_home') }}
        </a>
        <a href="{{ route('report.create') }}"
           class="inline-flex items-center px-3 py-1.5 bg-linear-to-r from-amber-700 to-orange-500 text-white text-sm font-semibold rounded-lg hover:from-amber-800 hover:to-orange-600 active:scale-[0.98] transition-all btn-touch interactive-lift">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('map.report') }}
        </a>
    </div>

    <div
        id="map"
        data-geojson-url="{{ route('api.reports.geojson', [], false) }}"
        data-no-address="{{ __('map.no_address') }}"
        data-view-details="{{ __('map.view_details') }}"
    ></div>
</div>
@endsection
