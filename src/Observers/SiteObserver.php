<?php

namespace Zoker\FilamentMultisite\Observers;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteRouteServiceProvider;

class SiteObserver
{
    public function created(Site $site): void
    {
        $this->cacheClear();
    }

    public function saved(Site $site): void
    {
        $this->cacheClear();
    }

    public function deleted(Site $site): void
    {
        $this->cacheClear();
    }

    private function cacheClear(): void
    {
        cache()->forget(FilamentMultisiteRouteServiceProvider::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY);
        cache()->forget(FilamentMultisiteRouteServiceProvider::TRANSLATABLE_LOCALES_CACHE_KEY);
    }
}
