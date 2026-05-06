<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.reports', function (User $user): bool {
    return $user->isAdmin() || $user->isManager();
});
