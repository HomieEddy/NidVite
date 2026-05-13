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

        $currentSessionId = session()->getId();
        $isCurrentSessionPersisted = is_string($currentSessionId)
            && $currentSessionId !== ''
            && $sessionIds->contains($currentSessionId);

        // On login, the new session row may not be persisted yet. Reserve one slot to avoid ending up at cap + 1.
        $maxExistingSessions = max(0, $maxSessions - ($isCurrentSessionPersisted ? 0 : 1));

        if ($sessionIds->count() <= $maxExistingSessions) {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->whereIn('id', $sessionIds->slice($maxExistingSessions)->values()->all())
            ->delete();
    }
}
