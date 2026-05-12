@extends('layouts.citizen')

@section('title', __('legal.terms.title'))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    <div class="citizen-card p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ __('legal.terms.title') }}
        </h1>
        <p class="text-sm text-gray-700">
            {{ __('legal.terms.intro') }}
        </p>
        <h2 class="text-lg font-semibold text-gray-900">{{ __('legal.terms.acceptable_use') }}</h2>
        <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
            <li>{{ __('legal.terms.no_fraud') }}</li>
            <li>{{ __('legal.terms.no_illegal_upload') }}</li>
            <li>{{ __('legal.terms.montreal_only') }}</li>
        </ul>
        <h2 class="text-lg font-semibold text-gray-900">{{ __('legal.terms.limitation_title') }}</h2>
        <p class="text-sm text-gray-700">
            {{ __('legal.terms.limitation_body') }}
        </p>
    </div>
</div>
@endsection
