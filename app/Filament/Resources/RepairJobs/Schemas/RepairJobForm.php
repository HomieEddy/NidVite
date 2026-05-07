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
                    ->label('UUID')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Select::make('reports')
                    ->label('Reports')
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
                    ->helperText('Select at least one received report to assign to this job.'),
                Textarea::make('description')
                    ->columnSpanFull(),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
                TextInput::make('status')
                    ->required()
                    ->default('planned'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                TextInput::make('estimated_cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('actual_cost')
                    ->numeric()
                    ->prefix('$'),
            ]);
    }
}
