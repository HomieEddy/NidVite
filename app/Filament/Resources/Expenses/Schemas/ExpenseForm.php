<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('repair_job_id')
                    ->relationship('repairJob', 'title')
                    ->required(),
                Select::make('material_id')
                    ->relationship('material', 'name'),
                Select::make('vendor_id')
                    ->relationship('vendorRelation', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('description')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('unit'),
                TextInput::make('unit_cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('subtotal')
                    ->numeric(),
                TextInput::make('tax_rate')
                    ->required()
                    ->numeric()
                    ->default(0.14975),
                TextInput::make('tax_amount')
                    ->numeric(),
                TextInput::make('total')
                    ->numeric(),
                DateTimePicker::make('incurred_at'),
                Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->required(),
            ]);
    }
}
