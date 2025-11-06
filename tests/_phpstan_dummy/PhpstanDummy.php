<?php

namespace Zoker\FilamentMultisite\Tests\_phpstan_dummy;

use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableCreateRecord;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableEditRecord;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableListRecord;

class PhpstanDummy
{
    use TranslatableCreateRecord, TranslatableEditRecord, TranslatableListRecord;
}
