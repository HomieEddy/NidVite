<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Actions\RepairJobs\SyncReportsToJobStatusAction;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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

        $record->loadMissing('reports');
        $reportIds = $record->reports->pluck('id')->all();

        app(SyncReportsToJobStatusAction::class)->execute($currentStatus, $reportIds);
    }
}
