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
            ->emptyStateHeading(__('filament.admin.resources.expenses.empty_state.heading'))
            ->emptyStateDescription(__('filament.admin.resources.expenses.empty_state.description'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('filament.admin.resources.expenses.actions.create'))
                    ->url(ExpenseResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->visible(fn (): bool => auth()->user()?->can('create', Expense::class) ?? false),
            ])
            ->columns([
                TextColumn::make('repairJob.title')
                    ->label(__('filament.admin.resources.expenses.fields.repair_job'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendorRelation.name')
                    ->label(__('filament.admin.resources.expenses.fields.vendor'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('material.name')
                    ->label(__('filament.admin.resources.expenses.fields.material'))
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
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('filament.admin.resources.expenses.fields.created_by'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
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
                    ->label(__('filament.admin.resources.expenses.fields.vendor')),
                Group::make('repairJob.title')
                    ->label(__('filament.admin.resources.expenses.fields.repair_job')),
                Group::make('incurred_at')
                    ->label(__('filament.admin.resources.expenses.fields.month'))
                    ->getTitleFromRecordUsing(fn ($record) => $record->incurred_at?->translatedFormat('M Y') ?? __('filament.admin.resources.expenses.fields.unknown')),
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
