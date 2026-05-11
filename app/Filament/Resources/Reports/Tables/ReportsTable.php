<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Actions\Reports\OverrideRoadValidationAction;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('filament.admin.resources.reports.empty_state.heading'))
            ->emptyStateDescription(__('filament.admin.resources.reports.empty_state.description'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('filament.admin.resources.reports.actions.create'))
                    ->url(ReportResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->visible(fn (): bool => auth()->user()?->can('create', Report::class) ?? false),
            ])
            ->columns([
                TextColumn::make('reporter_email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('neighborhood')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('borough')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'gray',
                        'verified' => 'info',
                        'scheduled' => 'warning',
                        'in_progress' => 'amber',
                        'repaired' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('road_validation_decision')
                    ->label(__('filament.admin.resources.reports.fields.road_validation_status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pass' => __('filament.admin.resources.reports.validation_decisions.pass'),
                        'fail_off_street' => __('filament.admin.resources.reports.validation_decisions.fail_off_street'),
                        'fail_low_accuracy' => __('filament.admin.resources.reports.validation_decisions.fail_low_accuracy'),
                        'fail_both' => __('filament.admin.resources.reports.validation_decisions.fail_both'),
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pass' => 'success',
                        'fail_off_street', 'fail_low_accuracy', 'fail_both' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('road_distance_meters')
                    ->label(__('filament.admin.resources.reports.fields.road_distance'))
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '-' : number_format($state, 1).' m')
                    ->sortable(),
                TextColumn::make('location')
                    ->label(__('filament.admin.resources.reports.fields.map'))
                    ->icon('heroicon-m-map-pin')
                    ->color('amber')
                    ->formatStateUsing(fn () => __('filament.admin.resources.reports.actions.view_on_map'))
                    ->action(
                        Action::make('viewLocation')
                            ->label(__('filament.admin.resources.reports.actions.view_on_map'))
                            ->modalHeading(__('filament.admin.resources.reports.actions.report_location'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel(__('filament.admin.resources.reports.actions.close'))
                            ->modalContent(function ($record): View {
                                $location = $record->coordinatePoint();

                                return view('filament.modals.report-location', [
                                    'report' => $record,
                                    'location' => $location,
                                ]);
                            })
                    )
                    ->tooltip(__('filament.admin.resources.reports.tooltips.map')),
                IconColumn::make('geofence_passed')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament.admin.resources.reports.filters.status'))
                    ->options([
                        'received' => __('filament.admin.resources.reports.statuses.received'),
                        'verified' => __('filament.admin.resources.reports.statuses.verified'),
                        'scheduled' => __('filament.admin.resources.reports.statuses.scheduled'),
                        'in_progress' => __('filament.admin.resources.reports.statuses.in_progress'),
                        'repaired' => __('filament.admin.resources.reports.statuses.repaired'),
                        'rejected' => __('filament.admin.resources.reports.statuses.rejected'),
                    ])
                    ->multiple(),
                SelectFilter::make('priority')
                    ->label(__('filament.admin.resources.reports.filters.priority'))
                    ->options([
                        'low' => __('filament.admin.resources.reports.priorities.low'),
                        'normal' => __('filament.admin.resources.reports.priorities.normal'),
                        'high' => __('filament.admin.resources.reports.priorities.high'),
                        'critical' => __('filament.admin.resources.reports.priorities.critical'),
                    ])
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->groups([
                Group::make('status')
                    ->label(__('filament.admin.resources.reports.groups.status'))
                    ->getTitleFromRecordUsing(fn ($record) => __('filament.admin.resources.reports.statuses.'.$record->status)),
                Group::make('neighborhood')
                    ->label(__('filament.admin.resources.reports.groups.neighborhood')),
                Group::make('borough')
                    ->label(__('filament.admin.resources.reports.groups.borough')),
                Group::make('priority')
                    ->label(__('filament.admin.resources.reports.groups.priority'))
                    ->getTitleFromRecordUsing(fn ($record) => __('filament.admin.resources.reports.priorities.'.$record->priority)),
                Group::make('created_at')
                    ->label(__('filament.admin.resources.reports.groups.date'))
                    ->getTitleFromRecordUsing(fn ($record) => $record->created_at->format('M Y')),
            ])
            ->defaultGroup('status')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('override_validation')
                    ->label(__('filament.admin.resources.reports.actions.override_validation'))
                    ->icon('heroicon-m-shield-check')
                    ->form([
                        Select::make('decision')
                            ->label(__('filament.admin.resources.reports.fields.override_decision'))
                            ->options([
                                'pass' => __('filament.admin.resources.reports.validation_decisions.pass'),
                                'fail_off_street' => __('filament.admin.resources.reports.validation_decisions.fail_off_street'),
                                'fail_low_accuracy' => __('filament.admin.resources.reports.validation_decisions.fail_low_accuracy'),
                                'fail_both' => __('filament.admin.resources.reports.validation_decisions.fail_both'),
                            ])
                            ->required(),
                        Textarea::make('audit_note')
                            ->label(__('filament.admin.resources.reports.fields.audit_note'))
                            ->required()
                            ->minLength(5),
                    ])
                    ->action(function (Report $record, array $data): void {
                        app(OverrideRoadValidationAction::class)(
                            $record,
                            (string) $data['decision'],
                            (string) $data['audit_note']
                        );
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
