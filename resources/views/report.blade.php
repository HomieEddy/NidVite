@extends('layouts.citizen')

@section('title', __('report.title') . ' - ' . config('app.name'))

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

<livewire:report-form />
@endsection

@push('scripts')
@if (config('captcha.sitekey'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @vite('resources/js/recaptcha-report.js')
@endif
@endpush
