<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\Report;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateRepairJob extends CreateRecord
{
    protected static string $resource = RepairJobResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $components = $this->form->getFlatComponents(withActions: false, withHidden: true);
        $reportIds = array_values(array_filter((array) (($components['reports'] ?? null)?->getState() ?? [])));

        if ($reportIds !== []) {
            $latestReportCreatedAt = Report::query()
                ->whereIn('id', $reportIds)
                ->max('created_at');

            if ($latestReportCreatedAt !== null && filled($data['scheduled_at'] ?? null) && Carbon::parse($data['scheduled_at'])->lt(Carbon::parse($latestReportCreatedAt))) {
                throw ValidationException::withMessages([
                    'scheduled_at' => __('validation.after_or_equal', [
                        'attribute' => __('filament.admin.fields_common.scheduled_at'),
                        'date' => $latestReportCreatedAt,
                    ]),
                ]);
            }
        }

        if (filled($data['scheduled_at'] ?? null) && filled($data['started_at'] ?? null) && Carbon::parse($data['started_at'])->lt(Carbon::parse($data['scheduled_at']))) {
            throw ValidationException::withMessages([
                'started_at' => __('validation.after_or_equal', [
                    'attribute' => __('filament.admin.fields_common.started_at'),
                    'date' => $data['scheduled_at'],
                ]),
            ]);
        }

        if (filled($data['started_at'] ?? null) && filled($data['completed_at'] ?? null) && Carbon::parse($data['completed_at'])->lt(Carbon::parse($data['started_at']))) {
            throw ValidationException::withMessages([
                'completed_at' => __('validation.after_or_equal', [
                    'attribute' => __('filament.admin.fields_common.completed_at'),
                    'date' => $data['started_at'],
                ]),
            ]);
        }

        $data['created_by'] = Filament::auth()->id();

        return $data;
    }
}
