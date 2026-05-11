<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reporter_email')
                    ->label(__('filament.admin.fields_common.reporter_email'))
                    ->email()
                    ->required(),
                TextInput::make('preferred_locale')
                    ->label(__('filament.admin.fields_common.preferred_locale'))
                    ->required()
                    ->default('fr'),
                TextInput::make('address')
                    ->label(__('filament.admin.fields_common.address')),
                TextInput::make('neighborhood')
                    ->label(__('filament.admin.resources.reports.fields.neighborhood')),
                TextInput::make('borough')
                    ->label(__('filament.admin.resources.reports.fields.borough')),
                Select::make('status')
                    ->label(__('filament.admin.fields_common.status'))
                    ->required()
                    ->options([
                        ReportStatus::Received->value => __('filament.admin.resources.reports.statuses.received'),
                        ReportStatus::Verified->value => __('filament.admin.resources.reports.statuses.verified'),
                        ReportStatus::Scheduled->value => __('filament.admin.resources.reports.statuses.scheduled'),
                        ReportStatus::InProgress->value => __('filament.admin.resources.reports.statuses.in_progress'),
                        ReportStatus::Repaired->value => __('filament.admin.resources.reports.statuses.repaired'),
                        ReportStatus::Rejected->value => __('filament.admin.resources.reports.statuses.rejected'),
                    ])
                    ->default('received'),
                TextInput::make('priority')
                    ->label(__('filament.admin.fields_common.priority'))
                    ->required()
                    ->default('normal'),
                Select::make('category_id')
                    ->relationship('category', 'id')
                    ->label(__('filament.admin.fields_common.category')),
                Textarea::make('description')
                    ->label(__('filament.admin.fields_common.description'))
                    ->columnSpanFull(),
                Toggle::make('geofence_passed')
                    ->label(__('filament.admin.fields_common.geofence_passed'))
                    ->required(),
                Toggle::make('is_spam')
                    ->label(__('filament.admin.fields_common.is_spam'))
                    ->required(),
                TextInput::make('rejection_reason')
                    ->label(__('filament.admin.fields_common.rejection_reason')),
                Textarea::make('admin_notes')
                    ->label(__('filament.admin.fields_common.admin_notes'))
                    ->columnSpanFull(),
                DateTimePicker::make('first_scheduled_at')
                    ->label(__('filament.admin.fields_common.first_scheduled_at')),
                DateTimePicker::make('first_started_at')
                    ->label(__('filament.admin.fields_common.first_started_at')),
                DateTimePicker::make('target_completion_at')
                    ->label(__('filament.admin.fields_common.target_completion_at')),
                DateTimePicker::make('completed_at')
                    ->label(__('filament.admin.fields_common.completed_at')),
                DateTimePicker::make('expires_at')
                    ->label(__('filament.admin.fields_common.expires_at'))
                    ->required(),
                TextInput::make('location')
                    ->label(__('filament.admin.fields_common.location')),
            ]);
    }
}
