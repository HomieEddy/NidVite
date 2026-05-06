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
                TextInput::make('vendor'),
                DateTimePicker::make('incurred_at'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
