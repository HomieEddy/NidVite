<div style="width: 100%;">
    <div style="margin-bottom: 12px;">
        <p style="font-size: 0.875rem; color: #374151;">
            <strong>{{ __('filament.admin.modals.report_location.address') }}</strong> {{ $report->address ?? __('filament.admin.modals.report_location.not_specified') }}
        </p>
        @if($report->neighborhood)
            <p style="font-size: 0.875rem; color: #6b7280;">{{ $report->neighborhood }}, {{ $report->borough }}</p>
        @endif
    </div>

    @if($location)
        @php
            $lat = (float) $location->lat;
            $lng = (float) $location->lng;
            $delta = 0.0045;
            $left = $lng - $delta;
            $right = $lng + $delta;
            $top = $lat + $delta;
            $bottom = $lat - $delta;
            $embedSrc = sprintf(
                'https://www.openstreetmap.org/export/embed.html?bbox=%F%%2C%F%%2C%F%%2C%F&layer=mapnik&marker=%F%%2C%F',
                $left,
                $bottom,
                $right,
                $top,
                $lat,
                $lng
            );
        @endphp

        <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; background: #f9fafb;">
            <iframe
                src="{{ $embedSrc }}"
                style="display: block; width: 100%; height: 280px; border: 0;"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="{{ __('filament.admin.modals.report_location.report_map') }}"
            ></iframe>
        </div>
    @else
        <div style="height: 200px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 0.5rem;">
            <p style="color: #6b7280; font-size: 0.875rem;">{{ __('filament.admin.modals.report_location.no_location') }}</p>
        </div>
    @endif
</div>
