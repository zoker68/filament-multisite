<?php

namespace Zoker\FilamentMultisite\Tests\_phpstan_dummy;

use Illuminate\Database\Eloquent\Model;
use Zoker\FilamentMultisite\Traits\HasMultisite;
use Zoker\FilamentMultisite\Traits\HasMultisiteResource;

/**
 * @property int $id
 * @property int $site_id
 */
class TestModel extends Model
{
    use HasMultisite, HasMultisiteResource;
}
