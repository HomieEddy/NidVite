<?php

namespace App\Filament\Resources\RepairJobs\Tables;

use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class RepairJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No repair jobs found')
            ->emptyStateDescription('Repair jobs will appear here once scheduled.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create Repair Job')
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
                    ->dateTime('M j, Y')
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('estimated_cost')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('actual_cost')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
            ])
            ->groups([
                Group::make('status')
                    ->label('Status')
                    ->getTitleFromRecordUsing(fn ($record) => ucfirst($record->status)),
                Group::make('creator.name')
                    ->label('Created By'),
                Group::make('scheduled_at')
                    ->label('Scheduled Month')
                    ->getTitleFromRecordUsing(fn ($record) => $record->scheduled_at?->format('M Y') ?? 'Not scheduled'),
            ])
            ->defaultGroup('status')
            ->recordActions([
                ViewAction::make()
                    ->record(fn (RepairJob $record): RepairJob => $record->load('reports'))
                    ->schema([
                        TextInput::make('title')
                            ->label('Title'),
                        TextInput::make('status')
                            ->label('Status'),
                        TextInput::make('scheduled_at')
                            ->label('Scheduled At'),
                        Repeater::make('reports')
                            ->label('Linked Reports')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('uuid')
                                    ->label('Report UUID'),
                                TextInput::make('address')
                                    ->label('Address'),
                                TextInput::make('status')
                                    ->label('Status'),
                            ])
                            ->columns(3),
                    ])
                    ->fillForm(fn (RepairJob $record): array => [
                        'title' => $record->title,
                        'status' => ucfirst(str_replace('_', ' ', $record->status)),
                        'scheduled_at' => optional($record->scheduled_at)->format('M j, Y H:i') ?? 'N/A',
                        'reports' => $record->reports
                            ->map(fn ($report): array => [
                                'uuid' => $report->uuid,
                                'address' => $report->address ?? 'Address not specified',
                                'status' => ucfirst(str_replace('_', ' ', $report->status)),
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->modalWidth('6xl'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
