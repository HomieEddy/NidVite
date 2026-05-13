<?php

namespace App\Filament\Widgets;

use App\Models\Material;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LowStockMaterialsOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $lowStockCount = Material::query()
            ->where('is_active', true)
            ->where('min_stock_alert', '>', 0)
            ->whereRaw('(current_stock - COALESCE(reserved_stock, 0)) < min_stock_alert')
            ->count();

        return [
            Stat::make(__('dashboard.low_stock_materials'), $lowStockCount)
                ->description(__('dashboard.below_threshold'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
