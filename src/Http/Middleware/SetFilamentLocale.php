<?php

namespace Zoker\FilamentMultisite\Http\Middleware;

use Cookie;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetFilamentLocale
{
    public function handle(Request $request, \Closure $next): Response
    {
        $locale = Cookie::get('filament_locale');

        if ($locale && array_key_exists($locale, config('multisite.filament_locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
