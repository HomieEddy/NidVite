<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Actions\RepairJobs\SyncReportsToJobStatusAction;
use App\Actions\RepairJobs\ValidateRepairJobDatesAction;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateRepairJob extends CreateRecord
{
    protected static string $resource = RepairJobResource::class;

    /**
     * Mutate the form data before creating a repair job.
     *
     * Validates the repair job dates and sets the created_by field.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $components = $this->form->getFlatComponents(withActions: false, withHidden: true);
        $reportIds = array_values(array_filter((array) (($components['reports'] ?? null)?->getState() ?? [])));

        app(ValidateRepairJobDatesAction::class)->execute($data, $reportIds);

        $data['created_by'] = Filament::auth()->id();

        return $data;
    }

    /**
     * After creating the repair job, synchronize the statuses of linked reports.
     *
     * @return void
     */
    protected function afterCreate(): void
    {
        /** @var RepairJob $record */
        $record = $this->record;

        $components = $this->form->getFlatComponents(withActions: false, withHidden: true);
        $reportsComponent = $components['reports'] ?? null;
        $reportIds = $reportsComponent !== null ? array_values(array_filter((array) $reportsComponent->getState())) : [];

        app(SyncReportsToJobStatusAction::class)->execute($record->status, $reportIds);
    }
}
