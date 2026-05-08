<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('filament.admin.resources.reports.exports.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        p { margin: 0 0 10px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; }
    </style>
</head>
<body>
    <h1>{{ __('filament.admin.resources.reports.exports.title') }}</h1>
    <p>{{ __('filament.admin.resources.reports.exports.range', ['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()]) }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('filament.admin.resources.reports.exports.columns.tracking_id') }}</th>
                <th>{{ __('filament.admin.resources.reports.exports.columns.status') }}</th>
                <th>{{ __('filament.admin.resources.reports.exports.columns.priority') }}</th>
                <th>{{ __('filament.admin.resources.reports.exports.columns.address') }}</th>
                <th>{{ __('filament.admin.resources.reports.exports.columns.neighborhood') }}</th>
                <th>{{ __('filament.admin.resources.reports.exports.columns.borough') }}</th>
                <th>{{ __('filament.admin.resources.reports.exports.columns.reported_at') }}</th>
                <th>{{ __('filament.admin.resources.reports.exports.columns.completed_at') }}</th>
                <th>{{ __('filament.admin.resources.reports.exports.columns.allocated_cost') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['tracking_id'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['priority'] }}</td>
                    <td>{{ $row['address'] }}</td>
                    <td>{{ $row['neighborhood'] }}</td>
                    <td>{{ $row['borough'] }}</td>
                    <td>{{ $row['reported_at'] }}</td>
                    <td>{{ $row['completed_at'] }}</td>
                    <td>{{ number_format((float) $row['allocated_cost_cad'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">{{ __('filament.admin.resources.reports.exports.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
