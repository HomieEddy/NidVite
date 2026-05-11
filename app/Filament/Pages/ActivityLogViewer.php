<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ActivityLogViewer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug = 'activity-log';

    protected string $view = 'filament.pages.activity-log-viewer';

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.activity_log.navigation_label');
    }

    public function getHeading(): string
    {
        return __('filament.activity_log.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->with('causer')->latest())
            ->emptyStateHeading(__('filament.activity_log.empty_heading'))
            ->columns([
                TextColumn::make('id')
                    ->label(__('filament.activity_log.columns.id'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('filament.activity_log.columns.date'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('causer_display')
                    ->label(__('filament.activity_log.columns.user'))
                    ->state(function (Activity $record): string {
                        if ($record->causer?->name) {
                            return $record->causer->name;
                        }

                        if ($record->causer?->email) {
                            return $record->causer->email;
                        }

                        if ($record->causer_id !== null) {
                            return __('filament.activity_log.values.user_id', ['id' => $record->causer_id]);
                        }

                        return __('filament.activity_log.values.system');
                    })
                    ->searchable(),
                TextColumn::make('event')
                    ->label(__('filament.activity_log.columns.event'))
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('filament.activity_log.columns.action'))
                    ->searchable(),
                TextColumn::make('log_name')
                    ->label(__('filament.activity_log.columns.log'))
                    ->placeholder('-')
                    ->badge()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subject_type')
                    ->label(__('filament.activity_log.columns.subject'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(),
                TextColumn::make('subject_id')
                    ->label(__('filament.activity_log.columns.subject_id'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label(__('filament.activity_log.columns.ip'))
                    ->state(function (Activity $record): string {
                        $ip = data_get($record->properties, 'ip_address_raw')
                            ?? data_get($record->properties, 'ip_address')
                            ?? data_get($record->properties, 'attributes.ip_address_raw')
                            ?? data_get($record->properties, 'attributes.ip_address');

                        return is_string($ip) && $ip !== '' ? $ip : '-';
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('causer_id')
                    ->label(__('filament.activity_log.filters.user'))
                    ->options(
                        User::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                    ),
                SelectFilter::make('description')
                    ->label(__('filament.activity_log.filters.action'))
                    ->options(
                        Activity::query()
                            ->select('description')
                            ->whereNotNull('description')
                            ->distinct()
                            ->orderBy('description')
                            ->pluck('description', 'description')
                            ->all()
                    ),
                SelectFilter::make('event')
                    ->label(__('filament.activity_log.filters.event'))
                    ->options(
                        Activity::query()
                            ->select('event')
                            ->whereNotNull('event')
                            ->distinct()
                            ->orderBy('event')
                            ->pluck('event', 'event')
                            ->all()
                    ),
                SelectFilter::make('log_name')
                    ->label(__('filament.activity_log.filters.log'))
                    ->options(
                        Activity::query()
                            ->select('log_name')
                            ->whereNotNull('log_name')
                            ->distinct()
                            ->orderBy('log_name')
                            ->pluck('log_name', 'log_name')
                            ->all()
                    ),
                Filter::make('created_at')
                    ->label(__('filament.activity_log.filters.date'))
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ]);
    }
}
