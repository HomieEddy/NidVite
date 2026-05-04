<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('reporter_email')
                    ->email()
                    ->required(),
                TextInput::make('preferred_locale')
                    ->required()
                    ->default('fr'),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('location_accuracy')
                    ->numeric(),
                TextInput::make('address'),
                TextInput::make('neighborhood'),
                TextInput::make('borough'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('priority')
                    ->required()
                    ->default('normal'),
                Select::make('category_id')
                    ->relationship('category', 'id'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('ip_address_hash')
                    ->required(),
                TextInput::make('ip_address_raw'),
                TextInput::make('user_agent_hash')
                    ->required(),
                Toggle::make('geofence_passed')
                    ->required(),
                DateTimePicker::make('geofence_checked_at'),
                TextInput::make('submission_duration_ms')
                    ->numeric(),
                Toggle::make('is_spam')
                    ->required(),
                TextInput::make('spam_score')
                    ->numeric(),
                TextInput::make('rejection_reason'),
                Textarea::make('admin_notes')
                    ->columnSpanFull(),
                DateTimePicker::make('first_scheduled_at'),
                DateTimePicker::make('first_started_at'),
                DateTimePicker::make('target_completion_at'),
                DateTimePicker::make('completed_at'),
                DateTimePicker::make('expires_at')
                    ->required(),
                TextInput::make('location'),
            ]);
    }
}
