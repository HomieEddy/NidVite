@extends('layouts.citizen')

@section('title', __('legal.privacy.title'))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    <div class="citizen-card p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ __('legal.privacy.title') }}
        </h1>
        <p class="text-sm text-gray-700">
            {{ __('legal.privacy.intro') }}
        </p>
        <h2 class="text-lg font-semibold text-gray-900">{{ __('legal.privacy.data_collected') }}</h2>
        <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
            <li>{{ __('legal.privacy.report_details') }}</li>
            <li>{{ __('legal.privacy.reporter_email') }}</li>
            <li>{{ __('legal.privacy.security_metadata') }}</li>
        </ul>
        <h2 class="text-lg font-semibold text-gray-900">{{ __('legal.privacy.retention_title') }}</h2>
        <p class="text-sm text-gray-700">
            {{ __('legal.privacy.retention_body') }}
        </p>
    </div>
</div>
@endsection
