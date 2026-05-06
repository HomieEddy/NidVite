<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ReportsMap extends Widget
{
    protected string $view = 'filament.widgets.reports-map';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';
}
