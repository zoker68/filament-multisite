<?php

namespace Zoker\FilamentMultisite\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void setCurrentSite(\Zoker\FilamentMultisite\Models\Site $site)
 * @method static \Zoker\FilamentMultisite\Models\Site getCurrentSite()
 */
class FilamentSiteManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zoker\FilamentMultisite\Services\FilamentSiteManager::class;
    }
}
