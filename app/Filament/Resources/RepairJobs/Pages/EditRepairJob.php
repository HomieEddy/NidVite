<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Actions\RepairJobs\SyncReportsToJobStatusAction;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepairJob extends EditRecord
{
    protected static string $resource = RepairJobResource::class;

    /**
     * Get the header actions for the edit repair job page.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * After saving the repair job, synchronize report statuses if the job status changed.
     */
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

        $record->loadMissing('reports');
        $reportIds = $record->reports->pluck('id')->all();

        app(SyncReportsToJobStatusAction::class)->execute($currentStatus, $reportIds);
    }
}
