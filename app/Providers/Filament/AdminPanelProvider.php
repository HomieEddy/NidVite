<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\LowStockMaterialsOverview;
use App\Filament\Widgets\ReportsByNeighborhood;
use App\Filament\Widgets\ReportsChart;
use App\Filament\Widgets\ReportsMap;
use App\Filament\Widgets\ReportsOverview;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnforceAdminSessionTimeout;
use App\Http\Middleware\SetLocale;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ReportsMap::class,
                ReportsOverview::class,
                LowStockMaterialsOverview::class,
                ReportsChart::class,
                ReportsByNeighborhood::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                EnforceAdminSessionTimeout::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable()->brandName('NidVite'),
            ])
            ->renderHook(
                'panels::global-search.after',
                fn (): string => Blade::render('@include("filament.components.language-toggle")'),
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => Blade::render('@include("vendor.filament.reverb-scripts")'),
            );
    }
}
