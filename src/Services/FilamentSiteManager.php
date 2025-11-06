<?php

namespace Zoker\FilamentMultisite\Services;

use Zoker\FilamentMultisite\Models\Site;

class FilamentSiteManager
{
    const string MULTISITE_FILAMENT_ACTIVE_SITE_ID_SESSION = 'multisite::filament.siteManager.activeSiteId';

    protected static ?Site $currentSite = null;

    public function setCurrentSite(Site $site): void
    {
        if ($site->isNot(self::$currentSite)) {
            session()->put(self::MULTISITE_FILAMENT_ACTIVE_SITE_ID_SESSION, $site->id);
        }

        static::$currentSite = $site;
    }

    public function getCurrentSite(): Site
    {
        if (! static::$currentSite) {
            $this->initializeCurrentSite();
        }

        /** @var Site */
        return static::$currentSite;
    }

    private function initializeCurrentSite(): void
    {
        $activeSiteId = session()->get(self::MULTISITE_FILAMENT_ACTIVE_SITE_ID_SESSION, null);

        /** @var Site $activeSite */
        $activeSite = $activeSiteId
            ? (Site::find($activeSiteId) ?? Site::first())
            : Site::first();

        $this->setCurrentSite($activeSite);
    }
}
