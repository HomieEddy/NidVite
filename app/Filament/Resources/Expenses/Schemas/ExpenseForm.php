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
                    ->label(__('filament.admin.fields_common.repair_job'))
                    ->required(),
                Select::make('material_id')
                    ->relationship('material', 'name')
                    ->label(__('filament.admin.fields_common.material')),
                Select::make('vendor_id')
                    ->relationship('vendorRelation', 'name')
                    ->label(__('filament.admin.fields_common.vendor'))
                    ->searchable()
                    ->preload(),
                TextInput::make('description')
                    ->label(__('filament.admin.fields_common.description'))
                    ->required(),
                TextInput::make('quantity')
                    ->label(__('filament.admin.fields_common.quantity'))
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('unit')
                    ->label(__('filament.admin.fields_common.unit')),
                TextInput::make('unit_cost')
                    ->label(__('filament.admin.fields_common.unit_cost'))
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('subtotal')
                    ->label(__('filament.admin.fields_common.subtotal'))
                    ->numeric(),
                TextInput::make('tax_rate')
                    ->label(__('filament.admin.fields_common.tax_rate'))
                    ->required()
                    ->numeric()
                    ->default(0.14975),
                TextInput::make('tax_amount')
                    ->label(__('filament.admin.fields_common.tax_amount'))
                    ->numeric(),
                TextInput::make('total')
                    ->label(__('filament.admin.fields_common.total'))
                    ->numeric(),
                DateTimePicker::make('incurred_at')
                    ->label(__('filament.admin.fields_common.incurred_at')),
                Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->label(__('filament.admin.fields_common.created_by'))
                    ->required(),
            ]);
    }
}
