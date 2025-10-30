<?php

namespace Zoker\FilamentMultisite\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Zoker\FilamentMultisite\Services\SiteManager setCurrentSite(\Zoker\FilamentMultisite\Models\Site|string|int $site)
 * @method static void setCurrentSiteById(int $id)
 * @method static void setCurrentSiteByRequest(\Illuminate\Http\Request $request)
 * @method static \Zoker\FilamentMultisite\Models\Site getCurrentSite()
 */
class SiteManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zoker\FilamentMultisite\Services\SiteManager::class;
    }
}
