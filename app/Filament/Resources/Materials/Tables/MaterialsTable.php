<?php

namespace App\Filament\Resources\Materials\Tables;

use App\Filament\Resources\Materials\MaterialResource;
use App\Models\Material;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('filament.admin.resources.materials.empty_state.heading'))
            ->emptyStateDescription(__('filament.admin.resources.materials.empty_state.description'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('filament.admin.resources.materials.actions.create'))
                    ->url(MaterialResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->visible(fn (): bool => auth()->user()?->can('create', Material::class) ?? false),
            ])
            ->columns([
                TextColumn::make('sku')
                    ->label(__('filament.admin.resources.materials.fields.sku'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label(__('filament.admin.fields_common.unit'))
                    ->sortable(),
                TextColumn::make('current_stock')
                    ->label(__('filament.admin.resources.materials.fields.current_stock'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('reserved_stock')
                    ->label(__('filament.admin.resources.materials.fields.reserved_stock'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('available_stock')
                    ->label(__('filament.admin.resources.materials.fields.available_stock'))
                    ->state(fn (Material $record): float => $record->available_stock)
                    ->numeric(decimalPlaces: 2)
                    ->sortable(query: fn ($query, string $direction) => $query->orderByRaw('(current_stock - reserved_stock) '.$direction)),
                TextColumn::make('min_stock_alert')
                    ->label(__('filament.admin.resources.materials.fields.min_stock_alert'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('filament.admin.fields_common.is_active'))
                    ->boolean()
                    ->sortable(),
            ])
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
