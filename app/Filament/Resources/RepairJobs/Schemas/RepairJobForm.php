<?php

namespace App\Filament\Resources\RepairJobs\Schemas;

use App\Models\Report;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RepairJobForm
{
    public static function configure(Schema $schema): Schema
    {
        $latestSelectedReportCreatedAt = function (Get $get): ?string {
            $reportIds = array_values(array_filter((array) $get('reports')));

            if ($reportIds === []) {
                return null;
            }

            return Report::query()
                ->whereIn('id', $reportIds)
                ->max('created_at');
        };

        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('filament.admin.fields_common.title'))
                    ->required(),
                Select::make('reports')
                    ->label(__('filament.admin.resources.repair_jobs.fields.reports'))
                    ->relationship(
                        'reports',
                        'public_tracking_id',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->select([
                                'reports.id',
                                'reports.public_tracking_id',
                                'reports.address',
                                'reports.borough',
                                'reports.neighborhood',
                            ])
                            ->where('reports.status', 'received')
                    )
                    ->getOptionLabelFromRecordUsing(fn (Report $record): string => ($label = implode(' | ', array_filter([
                        $record->public_tracking_id,
                        $record->address,
                        $record->borough,
                        $record->neighborhood,
                    ]))) !== '' ? $label : $record->public_tracking_id)
                    ->getOptionLabelsUsing(fn (array $values): array => Report::query()
                        ->whereIn('id', $values)
                        ->get(['id', 'public_tracking_id', 'address', 'borough', 'neighborhood'])
                        ->mapWithKeys(fn (Report $record): array => [
                            $record->id => ($label = implode(' | ', array_filter([
                                $record->public_tracking_id,
                                $record->address,
                                $record->borough,
                                $record->neighborhood,
                            ]))) !== '' ? $label : $record->public_tracking_id,
                        ])
                        ->all())
                    ->multiple()
                    ->live()
                    ->searchable()
                    ->preload()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minItems(fn (string $operation): ?int => $operation === 'create' ? 1 : null)
                    ->helperText(__('filament.admin.resources.repair_jobs.helper.select_received_reports')),
                Textarea::make('description')
                    ->label(__('filament.admin.fields_common.description'))
                    ->columnSpanFull(),
                DateTimePicker::make('scheduled_at')
                    ->label(__('filament.admin.fields_common.scheduled_at'))
                    ->minDate($latestSelectedReportCreatedAt)
                    ->rules(fn (Get $get): array => ($minimumDate = $latestSelectedReportCreatedAt($get)) === null
                        ? []
                        : ['after_or_equal:'.$minimumDate])
                    ->live(),
                DateTimePicker::make('started_at')
                    ->label(__('filament.admin.fields_common.started_at'))
                    ->minDate(fn (Get $get): ?string => $get('scheduled_at') ?? $latestSelectedReportCreatedAt($get))
                    ->rules(fn (Get $get): array => ($minimumDate = $get('scheduled_at') ?? $latestSelectedReportCreatedAt($get)) === null
                        ? []
                        : ['after_or_equal:'.$minimumDate])
                    ->live(),
                DateTimePicker::make('completed_at')
                    ->label(__('filament.admin.fields_common.completed_at'))
                    ->minDate(fn (Get $get): ?string => $get('started_at') ?? $get('scheduled_at') ?? $latestSelectedReportCreatedAt($get))
                    ->rules(fn (Get $get): array => ($minimumDate = $get('started_at') ?? $get('scheduled_at') ?? $latestSelectedReportCreatedAt($get)) === null
                        ? []
                        : ['after_or_equal:'.$minimumDate])
                    ->live(),
                Select::make('status')
                    ->label(__('filament.admin.fields_common.status'))
                    ->options([
                        'planned' => __('filament.admin.resources.repair_jobs.statuses.planned'),
                        'in_progress' => __('filament.admin.resources.repair_jobs.statuses.in_progress'),
                        'completed' => __('filament.admin.resources.repair_jobs.statuses.completed'),
                        'cancelled' => __('filament.admin.resources.repair_jobs.statuses.cancelled'),
                    ])
                    ->required()
                    ->default('planned'),
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
