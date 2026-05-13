<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('map.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body { height: 100%; margin: 0; }
        #map { width: 100%; height: 100%; }
    </style>
</head>
<body>
    <div
        id="map"
        data-geojson-url="{{ route('api.reports.geojson', request()->only(['status', 'neighborhood', 'borough'])) }}"
        data-no-address="{{ __('map.no_address') }}"
        data-view-details="{{ __('map.view_details') }}"
    ></div>
</body>
</html>
