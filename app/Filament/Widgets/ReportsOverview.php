<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReportsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('dashboard.open_reports'), $this->getOpenReportsCount())
                ->description(__('dashboard.awaiting_action'))
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make(__('dashboard.repairs_this_week'), $this->getRepairsThisWeekCount())
                ->description(__('dashboard.completed_7_days'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('dashboard.money_spent'), $this->getMoneySpent())
                ->description(__('dashboard.total_expenses'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make(__('dashboard.avg_repair_time'), $this->getAverageRepairTime())
                ->description(__('dashboard.days_from_received'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }

    private function getOpenReportsCount(): int
    {
        return Report::whereNotIn('status', ['repaired', 'rejected'])
            ->where('is_spam', false)
            ->count();
    }

    private function getRepairsThisWeekCount(): int
    {
        return Report::where('status', 'repaired')
            ->where('completed_at', '>=', now()->subDays(7))
            ->count();
    }

    private function getMoneySpent(): string
    {
        $total = (float) Expense::sum('total');

        return '$'.number_format($total, 2);
    }

    private function getAverageRepairTime(): string
    {
        $avg = Report::whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->where('status', 'repaired')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');

        if ($avg === null) {
            return 'N/A';
        }

        return round((float) $avg, 1).' '.__('dashboard.days');
    }
}
