<?php

namespace App\Filament\Resources\Materials\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label(__('filament.admin.resources.materials.fields.sku'))
                    ->required()
                    ->maxLength(100),
                TextInput::make('name')
                    ->label(__('filament.admin.fields_common.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('filament.admin.fields_common.description'))
                    ->rows(3),
                TextInput::make('unit')
                    ->label(__('filament.admin.fields_common.unit'))
                    ->required()
                    ->maxLength(50),
                TextInput::make('current_stock')
                    ->label(__('filament.admin.resources.materials.fields.current_stock'))
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('reserved_stock')
                    ->label(__('filament.admin.resources.materials.fields.reserved_stock'))
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('min_stock_alert')
                    ->label(__('filament.admin.resources.materials.fields.min_stock_alert'))
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('location')
                    ->label(__('filament.admin.fields_common.location'))
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label(__('filament.admin.fields_common.is_active'))
                    ->default(true),
            ]);
    }
}
