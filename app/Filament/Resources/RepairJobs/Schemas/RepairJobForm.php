<?php

namespace App\Filament\Resources\RepairJobs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RepairJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
                TextInput::make('status')
                    ->required()
                    ->default('planned'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                TextInput::make('estimated_cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('actual_cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('weather_conditions'),
            ]);
    }
}
