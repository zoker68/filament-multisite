<?php

namespace Zoker\FilamentMultisite\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Zoker\FilamentMultisite\Facades\SiteManager;

class MultisiteMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        SiteManager::setCurrentSiteByRequest($request);

        /** @var Route $route */
        $route = $request->route();
        $route->forgetParameter('multisite_prefix');

        return $next($request);
    }
}
