<?php

namespace Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Zoker\FilamentMultisite\Traits\Translatable\HasActiveSiteSwitcher;

trait TranslatableListRecord
{
    use HasActiveSiteSwitcher, Translatable;
}
