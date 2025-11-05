<?php

namespace Zoker\FilamentMultisite\Traits\Translatable;

use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentMultisite\Models\Site;

trait HasActiveSiteSwitcher
{
    public ?int $activeSiteId = null;

    public function bootHasActiveSiteSwitcher(): void
    {
        $activeSite = FilamentSiteManager::getCurrentSite();
        $this->activeSiteId = $activeSite->id;
    }

    public function updatedActiveSiteId(): void
    {
        $activeSite = Site::find($this->activeSiteId);

        FilamentSiteManager::setCurrentSite($activeSite);

        $this->setActiveSite($activeSite);
    }

    public function setActiveSite(Site $activeSite): void
    {
        if (method_exists($this, 'setActiveLocale')) {
            $this->setActiveLocale($activeSite->locale);
        } else {
            $this->activeLocale = $activeSite->locale;
            $this->updatedActiveLocale();
        }
    }
}
