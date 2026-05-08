<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament.admin.fields_common.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('contact_name')
                    ->label(__('filament.admin.fields_common.contact_name'))
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('filament.admin.fields_common.email'))
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label(__('filament.admin.fields_common.phone'))
                    ->maxLength(50),
                TextInput::make('address')
                    ->label(__('filament.admin.fields_common.address'))
                    ->maxLength(500),
                TextInput::make('website')
                    ->label(__('filament.admin.fields_common.website'))
                    ->url()
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label(__('filament.admin.fields_common.notes'))
                    ->rows(3),
                Toggle::make('is_active')
                    ->label(__('filament.admin.fields_common.is_active'))
                    ->default(true),
            ]);
    }
}
