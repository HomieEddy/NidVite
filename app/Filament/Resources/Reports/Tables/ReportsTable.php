<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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
            ->emptyStateHeading('No reports found')
            ->emptyStateDescription('Reports will appear here once citizens submit them.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create Report')
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
                TextColumn::make('location')
                    ->label('Map')
                    ->icon('heroicon-m-map-pin')
                    ->color('amber')
                    ->formatStateUsing(fn () => 'View on Map')
                    ->action(
                        Action::make('viewLocation')
                            ->label('View on Map')
                            ->modalHeading('Report Location')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalContent(function ($record): View {
                                $location = null;
                                if ($record->location !== null) {
                                    $location = \DB::selectOne(
                                        'SELECT ST_Y(location::geometry) as lat, ST_X(location::geometry) as lng FROM reports WHERE id = ?',
                                        [$record->id]
                                    );
                                }

                                return view('filament.modals.report-location', [
                                    'report' => $record,
                                    'location' => $location,
                                ]);
                            })
                    )
                    ->tooltip('Click to view on map'),
                IconColumn::make('geofence_passed')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'received' => 'Received',
                        'verified' => 'Verified',
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'repaired' => 'Repaired',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple(),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ])
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->groups([
                Group::make('status')
                    ->label('Status')
                    ->getTitleFromRecordUsing(fn ($record) => ucfirst($record->status)),
                Group::make('neighborhood')
                    ->label('Neighborhood'),
                Group::make('borough')
                    ->label('Borough'),
                Group::make('priority')
                    ->label('Priority')
                    ->getTitleFromRecordUsing(fn ($record) => ucfirst($record->priority)),
                Group::make('created_at')
                    ->label('Date')
                    ->getTitleFromRecordUsing(fn ($record) => $record->created_at->format('M Y')),
            ])
            ->defaultGroup('status')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
