<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationInbox extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $slug = 'notification-inbox';

    protected string $view = 'filament.pages.notification-inbox';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.notifications.navigation_label');
    }

    public function getHeading(): string
    {
        return __('filament.notifications.heading');
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', $user::class)
                    ->where('notifiable_id', $user->id)
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('filament.notifications.columns.date'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('data.message')
                    ->label(__('filament.notifications.columns.message'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('data.tracking_id')
                    ->label(__('filament.notifications.columns.tracking_id')),
                TextColumn::make('read_at')
                    ->label(__('filament.notifications.columns.read_at'))
                    ->formatStateUsing(fn ($state) => $state ? __('filament.notifications.read') : __('filament.notifications.unread')),
            ])
            ->actions([
                Action::make('mark_read')
                    ->label(__('filament.notifications.actions.mark_read'))
                    ->visible(fn (DatabaseNotification $record) => $record->read_at === null)
                    ->action(function (DatabaseNotification $record): void {
                        $record->markAsRead();
                    }),
            ])
            ->emptyStateHeading(__('filament.notifications.empty_heading'));
    }
}
