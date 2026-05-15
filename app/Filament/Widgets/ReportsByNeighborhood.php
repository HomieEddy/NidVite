<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;

class ReportsByNeighborhood extends ChartWidget
{
    public ?string $filter = '30d';

    protected static ?int $sort = 60;

    protected ?string $heading = null;

    protected ?string $description = null;

    public function __construct()
    {
        $this->heading = __('dashboard.neighborhood_cost_analysis');
        $this->description = __('dashboard.last_30_days');
    }

    protected function getType(): string
    {
        return 'bar';
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

        $this->description = match ($this->filter) {
            '7d' => __('dashboard.last_7_days'),
            '90d' => __('dashboard.last_90_days'),
            default => __('dashboard.last_30_days'),
        };

        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $neighborhoodCosts = Expense::query()
            ->join('repair_jobs', 'repair_jobs.id', '=', 'expenses.repair_job_id')
            ->join('job_reports', 'job_reports.repair_job_id', '=', 'repair_jobs.id')
            ->join('reports', 'reports.id', '=', 'job_reports.report_id')
            ->where('reports.status', '!=', 'rejected')
            ->whereNotNull('reports.neighborhood')
            ->where('reports.neighborhood', '!=', '')
            ->whereBetween('expenses.created_at', [$start, $end])
            ->selectRaw('reports.neighborhood as neighborhood, SUM(expenses.total * (job_reports.cost_allocation_percentage / 100.0)) as total_cost')
            ->groupBy('reports.neighborhood')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.cost_cad'),
                    'data' => $neighborhoodCosts->pluck('total_cost')->map(fn ($value) => round((float) $value, 2))->toArray(),
                    'backgroundColor' => '#D97706',
                ],
            ],
            'labels' => $neighborhoodCosts->pluck('neighborhood')->toArray(),
        ];
    }
}
