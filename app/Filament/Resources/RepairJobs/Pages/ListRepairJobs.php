<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Filament\Resources\RepairJobs\RepairJobResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepairJobs extends ListRecords
{
    protected static string $resource = RepairJobResource::class;

    /**
     * Get the header actions for the list repair jobs page.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
