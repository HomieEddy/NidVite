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
        <div style="padding: 14px; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #f9fafb;">
            <p style="margin: 0 0 6px; font-size: 0.875rem; color: #111827;">
                <strong>Coordinates:</strong> {{ number_format($location->lat, 6) }}, {{ number_format($location->lng, 6) }}
            </p>
            <p style="margin: 0; font-size: 0.75rem; color: #6b7280;">
                Open the location in a new tab to view the full interactive map.
            </p>
            <p style="margin: 10px 0 0;">
                <a href="https://www.openstreetmap.org/?mlat={{ $location->lat }}&mlon={{ $location->lng }}#map=15/{{ $location->lat }}/{{ $location->lng }}" target="_blank" rel="noopener noreferrer" style="font-size: 0.8rem; color: #d97706; text-decoration: none; font-weight: 600;">Open in OpenStreetMap</a>
            </p>
        </div>
    @else
        <div style="height: 200px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 0.5rem;">
            <p style="color: #6b7280; font-size: 0.875rem;">No location available for this report.</p>
        </div>
    @endif
</div>
