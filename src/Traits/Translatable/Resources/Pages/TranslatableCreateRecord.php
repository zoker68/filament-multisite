<?php

namespace Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentMultisite\Traits\Translatable\HasActiveSiteSwitcher;

trait TranslatableCreateRecord
{
    use HasActiveSiteSwitcher, Translatable;

    public function initializeTranslatableCreateRecord(): void
    {
        if (request()->getMethod() == 'POST') {
            app()->setLocale(FilamentSiteManager::getCurrentSite()->locale);
        }
    }
}
