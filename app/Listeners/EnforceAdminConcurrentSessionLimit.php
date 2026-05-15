<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

class EnforceAdminConcurrentSessionLimit
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        $userId = $user->getAuthIdentifier();

        if (! method_exists($user, 'isAdmin') || ! $user->isAdmin() || ! is_numeric($userId)) {
            return;
        }

        if (config('session.driver') !== 'database') {
            return;
        }

        $maxSessions = max(1, (int) config('admin-auth.max_concurrent_sessions', 2));

        $sessionIds = DB::table(config('session.table', 'sessions'))
            ->where('user_id', (int) $userId)
            ->orderByDesc('last_activity')
            ->pluck('id');

        if ($sessionIds->count() <= $maxSessions) {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->whereIn('id', $sessionIds->slice($maxSessions)->values()->all())
            ->delete();
    }
}
