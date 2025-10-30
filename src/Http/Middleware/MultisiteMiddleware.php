<?php

namespace Zoker\FilamentMultisite\Http\Middleware;

use Zoker\FilamentMultisite\Facades\SiteManager;

class MultisiteMiddleware
{
    public function handle($request, \Closure $next)
    {
        SiteManager::setCurrentSiteByRequest($request);

        $request->route()->forgetParameter('multisite_prefix');

        return $next($request);
    }
}
