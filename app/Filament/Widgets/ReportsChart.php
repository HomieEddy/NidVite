<?php

namespace App\Filament\Widgets;

use App\Models\RepairJob;
use Filament\Widgets\ChartWidget;

class ReportsChart extends ChartWidget
{
    public ?string $filter = '30d';

    protected static ?int $sort = 50;

    public function getHeading(): string
    {
        return __('dashboard.repair_velocity_trend');
    }

    public function getDescription(): ?string
    {
        return match ($this->filter) {
            '7d' => __('dashboard.last_7_days'),
            '90d' => __('dashboard.last_90_days'),
            default => __('dashboard.last_30_days'),
        };
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7d' => __('dashboard.last_7_days'),
            '30d' => __('dashboard.last_30_days'),
            '90d' => __('dashboard.last_90_days'),
        ];
    }

    protected function getData(): array
    {
        $days = match ($this->filter) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };

        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $counts = RepairJob::query()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $data = collect(range($days - 1, 0))
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
                    'label' => __('dashboard.repairs_completed'),
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
