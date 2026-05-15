<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session()->get('locale', $request->cookie('locale'));

        if (in_array($locale, ['fr', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
