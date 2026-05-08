<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reports Dataset Export</title>
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
    <h1>Reports Dataset Export</h1>
    <p>Range: {{ $startDate->toDateString() }} to {{ $endDate->toDateString() }}</p>

    <table>
        <thead>
            <tr>
                <th>Tracking ID</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Address</th>
                <th>Neighborhood</th>
                <th>Borough</th>
                <th>Reported At</th>
                <th>Completed At</th>
                <th>Allocated Cost (CAD)</th>
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
                    <td colspan="9">No records for selected date range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
