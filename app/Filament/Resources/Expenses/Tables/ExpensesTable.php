<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No expenses found')
            ->emptyStateDescription('Expenses will appear here once recorded.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create Expense')
                    ->url(ExpenseResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->visible(fn (): bool => auth()->user()?->can('create', Expense::class) ?? false),
            ])
            ->columns([
                TextColumn::make('repairJob.title')
                    ->label('Repair Job')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendorRelation.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('material.name')
                    ->label('Material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit')
                    ->searchable(),
                TextColumn::make('unit_cost')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('total')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('incurred_at')
                    ->dateTime('M j, Y')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('vendor_id')
                    ->relationship('vendorRelation', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->groups([
                Group::make('vendorRelation.name')
                    ->label('Vendor'),
                Group::make('repairJob.title')
                    ->label('Repair Job'),
                Group::make('incurred_at')
                    ->label('Month')
                    ->getTitleFromRecordUsing(fn ($record) => $record->incurred_at?->format('M Y') ?? 'Unknown'),
            ])
            ->defaultGroup('vendorRelation.name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
