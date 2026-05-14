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

    /**
     * Get the translated navigation label for the repair jobs resource.
     */
    public static function getNavigationLabel(): string
    {
        return __('filament.admin.resources.repair_jobs.navigation');
    }

    /**
     * Get the translated singular model label for the repair job resource.
     */
    public static function getModelLabel(): string
    {
        return __('filament.admin.resources.repair_jobs.singular');
    }

    /**
     * Get the translated plural model label for the repair jobs resource.
     */
    public static function getPluralModelLabel(): string
    {
        return __('filament.admin.resources.repair_jobs.plural');
    }

    /**
     * Configure the form schema used by the repair job resource.
     */
    public static function form(Schema $schema): Schema
    {
        return RepairJobForm::configure($schema);
    }

    /**
     * Configure the table schema used by the repair job resource.
     */
    public static function table(Table $table): Table
    {
        return RepairJobsTable::configure($table);
    }

    /**
     * Get related managers registered for the repair job resource.
     *
     * @return array<int, string>
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Get the page routes for the repair job resource.
     *
     * @return array<string, string>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRepairJobs::route('/'),
            'create' => CreateRepairJob::route('/create'),
            'edit' => EditRepairJob::route('/{record}/edit'),
        ];
    }
}
