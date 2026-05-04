<?php

namespace App\Filament\Resources\Reports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID'),
                TextColumn::make('reporter_email')
                    ->searchable(),
                TextColumn::make('preferred_locale')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('location_accuracy')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('neighborhood')
                    ->searchable(),
                TextColumn::make('borough')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('priority')
                    ->searchable(),
                TextColumn::make('category.id')
                    ->searchable(),
                TextColumn::make('ip_address_hash')
                    ->searchable(),
                TextColumn::make('ip_address_raw')
                    ->searchable(),
                TextColumn::make('user_agent_hash')
                    ->searchable(),
                IconColumn::make('geofence_passed')
                    ->boolean(),
                TextColumn::make('geofence_checked_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('submission_duration_ms')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_spam')
                    ->boolean(),
                TextColumn::make('spam_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rejection_reason')
                    ->searchable(),
                TextColumn::make('first_scheduled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('first_started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('target_completion_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
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
