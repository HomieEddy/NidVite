<?php

namespace App\Filament\Pages;

use App\Models\SuspiciousActivity;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SuspiciousActivityDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $slug = 'suspicious-activity';

    protected string $view = 'filament.pages.suspicious-activity-dashboard';

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.suspicious_activity.navigation_label');
    }

    public function getHeading(): string
    {
        return __('filament.suspicious_activity.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SuspiciousActivity::query()->with('report')->latest())
            ->emptyStateHeading(__('filament.suspicious_activity.empty_heading'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('filament.suspicious_activity.columns.date'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                BadgeColumn::make('severity')
                    ->label(__('filament.suspicious_activity.columns.severity'))
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'high',
                        'gray' => 'medium',
                    ])
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('type')
                    ->label(__('filament.suspicious_activity.columns.type'))
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', $state))
                    ->searchable(),
                TextColumn::make('reason')
                    ->label(__('filament.suspicious_activity.columns.reason'))
                    ->wrap()
                    ->limit(100)
                    ->searchable(),
                TextColumn::make('report.uuid')
                    ->label(__('filament.suspicious_activity.columns.report'))
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('filament.suspicious_activity.filters.type'))
                    ->options(
                        SuspiciousActivity::query()
                            ->select('type')
                            ->distinct()
                            ->orderBy('type')
                            ->pluck('type', 'type')
                            ->all()
                    ),
                SelectFilter::make('severity')
                    ->label(__('filament.suspicious_activity.filters.severity'))
                    ->options([
                        'critical' => 'CRITICAL',
                        'high' => 'HIGH',
                        'medium' => 'MEDIUM',
                    ]),
                Filter::make('created_at')
                    ->label(__('filament.suspicious_activity.filters.date'))
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
