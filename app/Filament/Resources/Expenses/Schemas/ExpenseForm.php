<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
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
                    ->numeric()
                    ->readOnly(),
                TextInput::make('gst_rate')
                    ->label(__('filament.admin.fields_common.gst_rate'))
                    ->required()
                    ->numeric()
                    ->default(0.0500),
                TextInput::make('qst_rate')
                    ->label(__('filament.admin.fields_common.qst_rate'))
                    ->required()
                    ->numeric()
                    ->default(0.0998),
                TextInput::make('tax_rate')
                    ->label(__('filament.admin.fields_common.tax_rate'))
                    ->numeric()
                    ->readOnly(),
                TextInput::make('tax_amount')
                    ->label(__('filament.admin.fields_common.tax_amount'))
                    ->numeric()
                    ->readOnly(),
                TextInput::make('total')
                    ->label(__('filament.admin.fields_common.total'))
                    ->numeric()
                    ->readOnly(),
                Select::make('cost_allocation_mode')
                    ->label(__('filament.admin.fields_common.cost_allocation_mode'))
                    ->options([
                        'equal_split' => __('filament.admin.resources.expenses.allocation.equal_split'),
                        'manual_override' => __('filament.admin.resources.expenses.allocation.manual_override'),
                    ])
                    ->default('equal_split')
                    ->required(),
                FileUpload::make('receipt_path')
                    ->label(__('filament.admin.fields_common.receipt'))
                    ->directory('expense-receipts')
                    ->disk('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']),
                DateTimePicker::make('incurred_at')
                    ->label(__('filament.admin.fields_common.incurred_at')),
                Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->label(__('filament.admin.fields_common.created_by'))
                    ->required(),
            ]);
    }
}
