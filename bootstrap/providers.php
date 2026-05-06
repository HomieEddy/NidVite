<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    BroadcastServiceProvider::class,
    AdminPanelProvider::class,
    FortifyServiceProvider::class,
    TelescopeServiceProvider::class,
];
