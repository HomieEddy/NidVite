<div style="width: 100%;">
    <div style="margin-bottom: 12px;">
        <p style="font-size: 0.875rem; color: #374151;">
            <strong>Address:</strong> {{ $report->address ?? 'Not specified' }}
        </p>
        @if($report->neighborhood)
            <p style="font-size: 0.875rem; color: #6b7280;">{{ $report->neighborhood }}, {{ $report->borough }}</p>
        @endif
    </div>

    @if($location)
        <iframe
            src="https://www.openstreetmap.org/export/embed.html?bbox={{ $location->lng - 0.01 }}%2C{{ $location->lat - 0.01 }}%2C{{ $location->lng + 0.01 }}%2C{{ $location->lat + 0.01 }}&layer=mapnik&marker={{ $location->lat }}%2C{{ $location->lng }}"
            width="100%"
            height="300"
            style="border: 1px solid #e5e7eb; border-radius: 0.5rem;"
            loading="lazy"
        ></iframe>
        <p style="margin-top: 8px; text-align: right;">
            <a href="https://www.openstreetmap.org/?mlat={{ $location->lat }}&mlon={{ $location->lng }}#map=15/{{ $location->lat }}/{{ $location->lng }}" target="_blank" style="font-size: 0.75rem; color: #d97706; text-decoration: none;">View Larger Map</a>
        </p>
    @else
        <div style="height: 200px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 0.5rem;">
            <p style="color: #6b7280; font-size: 0.875rem;">No location available for this report.</p>
        </div>
    @endif
</div>
