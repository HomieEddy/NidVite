<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceAdminSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isAdmin()) {
            return $next($request);
        }

        $timeoutMinutes = max(1, (int) config('admin-auth.session_timeout_minutes', 15));
        $lastActivity = (int) $request->session()->get('admin_last_activity', 0);
        $now = now()->getTimestamp();

        if ($lastActivity > 0 && ($now - $lastActivity) > ($timeoutMinutes * 60)) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login');
        }

        $request->session()->put('admin_last_activity', $now);

        return $next($request);
    }
}
