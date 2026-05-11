<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament.admin.fields_common.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('filament.admin.resources.users.fields.email'))
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->label(__('filament.admin.fields_common.password'))
                    ->password()
                    ->required(),
                Select::make('role_id')
                    ->relationship('role', 'id')
                    ->label(__('filament.admin.fields_common.role'))
                    ->required()
                    ->default(5),
                Textarea::make('two_factor_secret')
                    ->label(__('filament.admin.fields_common.two_factor_secret'))
                    ->columnSpanFull(),
                Textarea::make('two_factor_recovery_codes')
                    ->label(__('filament.admin.fields_common.two_factor_recovery_codes'))
                    ->columnSpanFull(),
                DateTimePicker::make('two_factor_confirmed_at')
                    ->label(__('filament.admin.fields_common.two_factor_confirmed_at')),
                DateTimePicker::make('last_login_at')
                    ->label(__('filament.admin.fields_common.last_login_at')),
                TextInput::make('locale')
                    ->label(__('filament.admin.fields_common.locale'))
                    ->required()
                    ->default('fr'),
                Toggle::make('is_active')
                    ->label(__('filament.admin.fields_common.is_active'))
                    ->required(),
            ]);
    }
}
