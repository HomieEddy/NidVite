<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\ChartWidget;

class ReportsChart extends ChartWidget
{
    protected ?string $heading = null;

    protected ?string $description = null;

    public function __construct()
    {
        $this->heading = __('dashboard.reports_over_time');
        $this->description = __('dashboard.last_30_days');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();

        $counts = Report::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $data = collect(range(29, 0))
            ->map(function (int $daysAgo) use ($counts): array {
                $date = now()->subDays($daysAgo)->format('Y-m-d');

                return [
                    'date' => $date,
                    'count' => (int) ($counts[$date] ?? 0),
                ];
            });

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.reports'),
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => '#D97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }
}
