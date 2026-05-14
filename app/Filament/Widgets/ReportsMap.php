<?php

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Models\Report;
use Filament\Widgets\Widget;

class ReportsMap extends Widget
{
    private const BOROUGH_NA = 'N/A';

    protected string $view = 'filament.widgets.reports-map';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public string $selectedStatus = '';

    public string $selectedBorough = '';

    public string $appliedStatus = '';

    public string $appliedBorough = '';

    /**
     * @var array<string, string>|null
     */
    private ?array $availableBoroughs = null;

    public function applyFilters(): void
    {
        $this->appliedStatus = $this->normalizeStatus($this->selectedStatus);
        $this->appliedBorough = $this->normalizeBorough($this->selectedBorough);
    }

    public function resetFilters(): void
    {
        $this->selectedStatus = '';
        $this->selectedBorough = '';
        $this->appliedStatus = '';
        $this->appliedBorough = '';
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

    /**
     * @return array<string, string>
     */
    public function getAvailableBoroughs(): array
    {
        if ($this->availableBoroughs !== null) {
            return $this->availableBoroughs;
        }

        $this->availableBoroughs = Report::query()
            ->whereNotNull('borough')
            ->where('borough', '!=', '')
            ->where('borough', '!=', self::BOROUGH_NA)
            ->orderBy('borough')
            ->distinct()
            ->pluck('borough', 'borough')
            ->toArray();

        return $this->availableBoroughs;
    }

    public function getMapSrcProperty(): string
    {
        $params = ['embed' => 1];

        if ($this->appliedStatus !== '') {
            $params['status'] = $this->appliedStatus;
        }

        if ($this->appliedBorough !== '') {
            $params['borough'] = $this->appliedBorough;
        }

        return route('map.public', $params);
    }

    private function normalizeStatus(string $status): string
    {
        return array_key_exists($status, $this->getAvailableStatuses()) ? $status : '';
    }

    private function normalizeBorough(string $borough): string
    {
        return array_key_exists($borough, $this->getAvailableBoroughs()) ? $borough : '';
    }
}
