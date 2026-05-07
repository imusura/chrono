<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language', 'hr');
        $supported = ['hr', 'en'];

        app()->setLocale(in_array($locale, $supported) ? $locale : 'hr');

        return $next($request);
    }
}
