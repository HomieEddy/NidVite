<?php

namespace App\Filament\Resources\RepairJobs\Schemas;

use App\Models\Report;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RepairJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label(__('filament.admin.resources.repair_jobs.fields.uuid'))
                    ->required(),
                TextInput::make('title')
                    ->label(__('filament.admin.fields_common.title'))
                    ->required(),
                Select::make('reports')
                    ->label(__('filament.admin.resources.repair_jobs.fields.reports'))
                    ->relationship(
                        'reports',
                        'uuid',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->where('reports.status', 'received')
                    )
                    ->getOptionLabelFromRecordUsing(fn (Report $record): string => implode(' | ', array_filter([
                        $record->address,
                        $record->borough,
                        $record->neighborhood,
                    ])))
                    ->getOptionLabelsUsing(fn (array $values): array => Report::query()
                        ->whereIn('id', $values)
                        ->get(['id', 'address', 'borough', 'neighborhood'])
                        ->mapWithKeys(fn (Report $record): array => [
                            $record->id => implode(' | ', array_filter([
                                $record->address,
                                $record->borough,
                                $record->neighborhood,
                            ])),
                        ])
                        ->all())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minItems(fn (string $operation): ?int => $operation === 'create' ? 1 : null)
                    ->helperText(__('filament.admin.resources.repair_jobs.helper.select_received_reports')),
                Textarea::make('description')
                    ->label(__('filament.admin.fields_common.description'))
                    ->columnSpanFull(),
                DateTimePicker::make('scheduled_at')
                    ->label(__('filament.admin.fields_common.scheduled_at')),
                DateTimePicker::make('started_at')
                    ->label(__('filament.admin.fields_common.started_at')),
                DateTimePicker::make('completed_at')
                    ->label(__('filament.admin.fields_common.completed_at')),
                TextInput::make('status')
                    ->label(__('filament.admin.fields_common.status'))
                    ->required()
                    ->default('planned'),
                TextInput::make('created_by')
                    ->label(__('filament.admin.fields_common.created_by'))
                    ->required()
                    ->numeric(),
                TextInput::make('estimated_cost')
                    ->label(__('filament.admin.fields_common.estimated_cost'))
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('actual_cost')
                    ->label(__('filament.admin.fields_common.actual_cost'))
                    ->numeric()
                    ->prefix('$'),
            ]);
    }
}
