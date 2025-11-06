<?php

namespace Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Zoker\FilamentMultisite\Traits\Translatable\HasActiveSiteSwitcher;

trait TranslatableCreateRecord
{
    use HasActiveSiteSwitcher, Translatable;
}
