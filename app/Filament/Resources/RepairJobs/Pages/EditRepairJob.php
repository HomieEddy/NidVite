<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;

class EditRepairJob extends EditRecord
{
    protected static string $resource = RepairJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var RepairJob $record */
        $record = $this->record;

        $originalStatus = $record->getOriginal('status');
        $currentStatus = $record->status;

        // Only sync if status actually changed
        if ($originalStatus === $currentStatus) {
            return;
        }

        $targetReportStatus = $this->mapJobStatusToReportStatus($currentStatus);

        if ($targetReportStatus === null) {
            return;
        }

        $record->loadMissing('reports');

        foreach ($record->reports as $report) {
            try {
                $report->transitionTo($targetReportStatus);
            } catch (InvalidArgumentException $e) {
                report($e);
            }
        }
    }

    private function mapJobStatusToReportStatus(string $jobStatus): ?string
    {
        return match ($jobStatus) {
            'planned' => 'scheduled',
            'in_progress' => 'in_progress',
            'completed' => 'repaired',
            default => null,
        };
    }
}
