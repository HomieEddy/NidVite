<?php

namespace App\Support\Http;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

class PublicResponseCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        if (! parent::shouldCacheRequest($request)) {
            return false;
        }

        return ! $request->is([
            'admin',
            'admin/*',
            'livewire',
            'livewire/*',
            'livewire-*',
            'livewire-*/*',
            'login',
            'logout',
            'register',
            'password/*',
        ]);
    }
}
