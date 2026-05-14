<?php

namespace App\Filament\Resources\RepairJobs\Tables;

use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class RepairJobsTable
{
    /**
     * Configure the repair jobs table for Filament admin.
     *
     * Sets up columns, filters, and actions for the repair jobs listing.
     *
     * @param Table $table
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('filament.admin.resources.repair_jobs.empty_state.heading'))
            ->emptyStateDescription(__('filament.admin.resources.repair_jobs.empty_state.description'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('filament.admin.resources.repair_jobs.actions.create'))
                    ->url(RepairJobResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->visible(fn (): bool => auth()->user()?->can('create', RepairJob::class) ?? false),
            ])
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planned' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label(__('filament.admin.resources.repair_jobs.fields.created_by'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('estimated_cost')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('actual_cost')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'planned' => __('filament.admin.resources.repair_jobs.statuses.planned'),
                        'in_progress' => __('filament.admin.resources.repair_jobs.statuses.in_progress'),
                        'completed' => __('filament.admin.resources.repair_jobs.statuses.completed'),
                        'cancelled' => __('filament.admin.resources.repair_jobs.statuses.cancelled'),
                    ])
                    ->multiple(),
            ])
            ->groups([
                Group::make('status')
                    ->label(__('filament.admin.resources.repair_jobs.fields.status'))
                    ->getTitleFromRecordUsing(fn ($record) => __('filament.admin.resources.repair_jobs.statuses.'.$record->status)),
                Group::make('creator.name')
                    ->label(__('filament.admin.resources.repair_jobs.fields.created_by')),
                Group::make('scheduled_at')
                    ->label(__('filament.admin.resources.repair_jobs.fields.scheduled_month'))
                    ->getTitleFromRecordUsing(fn ($record) => $record->scheduled_at?->translatedFormat('M Y') ?? __('filament.admin.resources.repair_jobs.helper.not_scheduled')),
            ])
            ->defaultGroup('status')
            ->recordActions([
                ViewAction::make()
                    ->record(fn (RepairJob $record): RepairJob => $record->load('reports'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('filament.admin.resources.repair_jobs.fields.title')),
                        TextInput::make('status')
                            ->label(__('filament.admin.resources.repair_jobs.fields.status')),
                        TextInput::make('scheduled_at')
                            ->label(__('filament.admin.resources.repair_jobs.fields.scheduled_at')),
                        Repeater::make('reports')
                            ->label(__('filament.admin.resources.repair_jobs.fields.linked_reports'))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('tracking_id')
                                    ->label(__('filament.admin.resources.repair_jobs.fields.tracking_id')),
                                TextInput::make('address')
                                    ->label(__('filament.admin.resources.repair_jobs.fields.address')),
                                TextInput::make('status')
                                    ->label(__('filament.admin.resources.repair_jobs.fields.status')),
                            ])
                            ->columns(3),
                    ])
                    ->fillForm(fn (RepairJob $record): array => [
                        'title' => $record->title,
                        'status' => __('filament.admin.resources.repair_jobs.statuses.'.$record->status),
                        'scheduled_at' => optional($record->scheduled_at)->translatedFormat('M j, Y H:i') ?? __('filament.admin.resources.repair_jobs.fields.status_fallback'),
                        'reports' => $record->reports
                            ->map(fn ($report): array => [
                                'tracking_id' => $report->public_tracking_id,
                                'address' => $report->address ?? __('filament.admin.resources.repair_jobs.fields.address_fallback'),
                                'status' => __('filament.admin.resources.reports.statuses.'.$report->status),
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->modalWidth('6xl'),
                Action::make('assign_workers')
                    ->label(__('filament.admin.resources.repair_jobs.actions.assign_workers'))
                    ->visible(fn (RepairJob $record): bool => auth()->user()?->can('assignWorkers', $record) ?? false)
                    ->authorize(fn (RepairJob $record): bool => auth()->user()?->can('assignWorkers', $record) ?? false)
                    ->form([
                        Select::make('worker_ids')
                            ->label(__('filament.admin.resources.repair_jobs.fields.workers'))
                            ->options(fn (): array => User::query()
                                ->where('is_active', true)
                                ->whereHas('role', fn ($query) => $query->where('slug', 'service_worker'))
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (RepairJob $record, array $data): void {
                        $record->assignWorkers($data['worker_ids'] ?? []);
                    }),
                Action::make('self_assign')
                    ->label(__('filament.admin.resources.repair_jobs.actions.self_assign'))
                    ->visible(fn (RepairJob $record): bool => auth()->user()?->can('selfAssign', $record)
                        && ! $record->users()->whereKey(auth()->id())->exists())
                    ->authorize(fn (RepairJob $record): bool => auth()->user()?->can('selfAssign', $record) ?? false)
                    ->action(function (RepairJob $record): void {
                        $record->selfAssign(auth()->user());
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
