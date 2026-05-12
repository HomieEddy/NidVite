<?php

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use Filament\Widgets\Widget;

class ReportsMap extends Widget
{
    protected string $view = 'filament.widgets.reports-map';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public string $selectedStatus = '';

    public string $appliedStatus = '';

    public function applyFilters(): void
    {
        $this->appliedStatus = $this->normalizeStatus($this->selectedStatus);
    }

    public function resetFilters(): void
    {
        $this->selectedStatus = '';
        $this->appliedStatus = '';
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableStatuses(): array
    {
        return collect(ReportStatus::values())
            ->filter(fn (string $value): bool => $value !== ReportStatus::Rejected->value)
            ->mapWithKeys(fn (string $value): array => [
                $value => __('filament.admin.resources.reports.statuses.'.$value),
            ])
            ->all();
    }

    public function getMapSrcProperty(): string
    {
        $params = ['embed' => 1];

        if ($this->appliedStatus !== '') {
            $params['status'] = $this->appliedStatus;
        }

        return route('map.public', $params);
    }

    private function normalizeStatus(string $status): string
    {
        return array_key_exists($status, $this->getAvailableStatuses()) ? $status : '';
    }
}
