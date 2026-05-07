@extends('layouts.citizen')

@section('title', __('tracking.Suivi de signalement') . ' - ' . config('app.name'))

@push('styles')
@if($location)
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
    @php
        $statusClass = match($report->status) {
            'received' => 'bg-gray-100 text-gray-800',
            'verified' => 'bg-blue-100 text-blue-800',
            'scheduled' => 'bg-yellow-100 text-yellow-800',
            'in_progress' => 'bg-orange-100 text-orange-800',
            'repaired' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    @endphp

    {{-- Report Card --}}
    <div class="citizen-card p-5 mb-4 animate-fade-in">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900">{{ __('tracking.Votre signalement') }}</h2>
            <span class="status-pill {{ $statusClass }}">
                {{ __("report.status.{$report->status}") }}
            </span>
        </div>

        <div class="space-y-2.5">
            <div class="flex items-center text-sm">
                <span class="text-gray-500 w-20 shrink-0">{{ __('tracking.Numéro') }}</span>
                <span class="font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded text-xs">{{ $report->uuid }}</span>
            </div>
            <div class="flex items-center text-sm">
                <span class="text-gray-500 w-20 shrink-0">{{ __('tracking.Date') }}</span>
                <span class="text-gray-700">{{ $report->created_at->translatedFormat('j F Y') }}</span>
            </div>
            @if($report->category)
                <div class="flex items-center text-sm">
                    <span class="text-gray-500 w-20 shrink-0">{{ __('tracking.Catégorie') }}</span>
                    <span class="text-gray-700">{{ app()->getLocale() === 'fr' ? $report->category->label_fr : $report->category->label_en }}</span>
                </div>
            @endif
            @if($report->address)
                <div class="flex items-start text-sm">
                    <span class="text-gray-500 w-20 shrink-0">{{ __('tracking.Adresse') }}</span>
                    <span class="text-gray-700">{{ $report->address }}</span>
                </div>
            @endif
        </div>

        @if($location)
            <div id="tracking-map" class="mt-4 border border-gray-200" data-lat="{{ $location->lat }}" data-lng="{{ $location->lng }}"></div>
        @endif

        @if(!empty($photoUrls))
            <div class="mt-4">
                <p class="text-sm font-semibold text-gray-800 mb-2">{{ __('report.photos') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($photoUrls as $photoUrl)
                        <a
                            href="{{ $photoUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-amber-200 text-amber-700 bg-amber-50 hover:bg-amber-100 transition text-sm"
                        >
                            {{ __('report.photos') }} #{{ $loop->iteration }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Timeline --}}
    <div class="citizen-card p-5 animate-slide-up">
        <h3 class="text-base font-bold text-gray-900 mb-4">{{ __('tracking.Historique') }}</h3>

        <div class="space-y-0">
            @php
                $steps = [
                    ['status' => 'received', 'label' => __('report.status.received'), 'icon' => 'M4 7h16M4 12h16m-7 5h7'],
                    ['status' => 'verified', 'label' => __('report.status.verified'), 'icon' => 'm21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z'],
                    ['status' => 'scheduled', 'label' => __('report.status.scheduled'), 'icon' => 'M8 2v3m8-3v3M3.5 9.5h17M5 6.5h14a1.5 1.5 0 0 1 1.5 1.5v11A1.5 1.5 0 0 1 19 20.5H5A1.5 1.5 0 0 1 3.5 19V8A1.5 1.5 0 0 1 5 6.5Z'],
                    ['status' => 'in_progress', 'label' => __('report.status.in_progress'), 'icon' => 'M12 3v4m0 10v4m9-9h-4M7 12H3m15.364 6.364-2.828-2.828M8.464 8.464 5.636 5.636m12.728 0-2.828 2.828M8.464 15.536l-2.828 2.828'],
                    ['status' => 'repaired', 'label' => __('report.status.repaired'), 'icon' => 'm5 12 4 4 10-10'],
                ];
                $currentIndex = array_search($report->status, array_column($steps, 'status'));
                if ($currentIndex === false && $report->status === 'rejected') {
                    $currentIndex = -1;
                }
            @endphp

            @if($report->status === 'rejected')
                <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200">
                    <span class="w-8 h-8 rounded-full bg-red-100 text-red-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 6l12 12M18 6 6 18"/>
                        </svg>
                    </span>
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
                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $index <= $currentIndex ? 'bg-amber-100 text-amber-800' : 'bg-white text-gray-400 border border-gray-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="{{ $step['icon'] }}"/>
                            </svg>
                        </span>
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

@endsection
