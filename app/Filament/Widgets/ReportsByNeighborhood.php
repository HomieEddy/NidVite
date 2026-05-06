<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\ChartWidget;

class ReportsByNeighborhood extends ChartWidget
{
    protected ?string $heading = null;

    protected ?string $description = null;

    public function __construct()
    {
        $this->heading = __('dashboard.reports_by_neighborhood');
        $this->description = __('dashboard.top_10');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $neighborhoods = Report::where('status', '!=', 'rejected')
            ->whereNotNull('neighborhood')
            ->where('neighborhood', '!=', '')
            ->selectRaw('neighborhood, COUNT(*) as count')
            ->groupBy('neighborhood')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.reports'),
                    'data' => $neighborhoods->pluck('count')->toArray(),
                    'backgroundColor' => '#D97706',
                ],
            ],
            'labels' => $neighborhoods->pluck('neighborhood')->toArray(),
        ];
    }
}
