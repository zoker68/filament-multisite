<?php

namespace Zoker\FilamentMultisite\Services;

use Zoker\FilamentMultisite\Models\Site;

class FilamentSiteManager
{
    protected static ?Site $currentSite = null;

    public function setCurrentSite(Site $site): void
    {
        if ($site->isNot(self::$currentSite)) {
            session()->put('multisite::filament.siteManager.activeSiteId', $site->id);
        }

        static::$currentSite = $site;
    }

    public function getCurrentSite(): Site
    {
        if (! static::$currentSite) {
            $this->initializeCurrentSite();
        }

        return static::$currentSite;
    }

    private function initializeCurrentSite(): void
    {
        $activeSiteId = session()->get('multisite::filament.siteManager.activeSiteId', null);

        $activeSite = $activeSiteId
            ? (Site::find($activeSiteId) ?? Site::first())
            : Site::first();

        $this->setCurrentSite($activeSite);
    }
}
