<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Actions\RepairJobs\SyncReportsToJobStatusAction;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

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

        if (! $record->wasChanged('status')) {
            return;
        }

        $record->loadMissing('reports');
        $reportIds = $record->reports->pluck('id')->all();

        try {
            app(SyncReportsToJobStatusAction::class)->execute($record->status, $reportIds);
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->danger()
                ->title('Failed to sync linked report statuses.')
                ->send();
        }
    }
}
