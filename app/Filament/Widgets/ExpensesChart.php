<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Filament\Widgets\ChartWidget;

class ExpensesChart extends ChartWidget
{
    protected ?string $heading = null;

    protected ?string $description = null;

    public function __construct()
    {
        $this->heading = __('dashboard.expenses_by_category');
        $this->description = __('dashboard.current_month');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $categories = ExpenseCategory::all();

        $totals = Expense::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $data = $categories->map(function ($category) use ($totals): array {
            return [
                'label' => app()->getLocale() === 'fr' ? $category->label_fr : $category->label_en,
                'total' => (float) ($totals[$category->id] ?? 0),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.amount_cad'),
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#D97706',
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }
}
