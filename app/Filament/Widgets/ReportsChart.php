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
        $data = collect(range(29, 0))
            ->map(function (int $daysAgo): array {
                $date = now()->subDays($daysAgo);

                return [
                    'date' => $date->format('Y-m-d'),
                    'count' => Report::whereDate('created_at', $date)->count(),
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
