<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('map.title') }} — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body { font-family: system-ui, -apple-system, sans-serif; }
        #map { height: {{ $embedded ? "100%" : "100vh" }}; width: 100%; }
        .map-header {
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            z-index: 1000;
            background: white;
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .map-header h1 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1f2937;
        }
        .map-header p {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .map-header .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .map-header a {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s;
        }
        .map-header .btn-primary {
            background: #d97706;
            color: white;
        }
        .map-header .btn-primary:hover {
            background: #b45309;
        }
        .map-header .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        .map-header .btn-secondary:hover {
            background: #e5e7eb;
        }
        .report-popup {
            min-width: 200px;
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
            padding: 4px 12px;
            background: #d97706;
            color: white;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
        }
        .report-popup a:hover {
            background: #b45309;
        }
        @media (max-width: 640px) {
            .map-header { padding: 8px 12px; }
            .map-header h1 { font-size: 1rem; }
        }
    </style>
</head>
<body>
    @if (! $embedded)
        <div class="map-header">
            <div>
                <h1>{{ __('map.title') }}</h1>
                <p>{{ __('map.subtitle') }}</p>
            </div>
            <div class="actions">
                <a href="{{ route('report.create') }}" class="btn-secondary">{{ __('map.report') }}</a>
                <a href="/" class="btn-primary">{{ __('map.back_home') }}</a>
            </div>
        </div>
    @endif

    <div id="map"></div>

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
</body>
</html>
