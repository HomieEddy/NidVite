<?php

namespace App\Filament\Resources\RepairJobs;

use App\Filament\Resources\RepairJobs\Pages\CreateRepairJob;
use App\Filament\Resources\RepairJobs\Pages\EditRepairJob;
use App\Filament\Resources\RepairJobs\Pages\ListRepairJobs;
use App\Filament\Resources\RepairJobs\Schemas\RepairJobForm;
use App\Filament\Resources\RepairJobs\Tables\RepairJobsTable;
use App\Models\RepairJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RepairJobResource extends Resource
{
    protected static ?string $model = RepairJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    public static function form(Schema $schema): Schema
    {
        return RepairJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepairJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepairJobs::route('/'),
            'create' => CreateRepairJob::route('/create'),
            'edit' => EditRepairJob::route('/{record}/edit'),
        ];
    }
}
