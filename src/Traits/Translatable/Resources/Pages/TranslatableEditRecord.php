<?php

namespace Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Zoker\FilamentMultisite\Traits\Translatable\HasActiveSiteSwitcher;

trait TranslatableEditRecord
{
    use HasActiveSiteSwitcher, Translatable;
}
