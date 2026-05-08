<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RemovePermissionsPolicyHeader
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->remove('Permissions-Policy');

        return $response;
    }
}
