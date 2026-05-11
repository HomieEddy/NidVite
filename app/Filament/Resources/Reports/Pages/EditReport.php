<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Actions\Reports\TransitionReportStatusAction;
use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditReport extends EditRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Report) {
            return parent::handleRecordUpdate($record, $data);
        }

        $nextStatus = isset($data['status']) ? (string) $data['status'] : null;
        $targetStatus = $nextStatus ?? $record->status;

        if ($nextStatus !== null && $nextStatus !== $record->status) {
            app(TransitionReportStatusAction::class)(
                $record,
                $nextStatus,
                isset($data['rejection_reason']) ? (string) $data['rejection_reason'] : null
            );

            $targetStatus = $nextStatus;

            unset($data['status'], $data['rejection_reason']);
        }

        if ($targetStatus !== ReportStatus::Rejected->value) {
            $data['rejection_reason'] = null;
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
