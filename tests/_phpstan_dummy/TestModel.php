<?php

namespace Zoker\FilamentMultisite\Tests\_phpstan_dummy;

use Zoker\FilamentMultisite\Traits\HasMultisite;

/**
 * @property int $id
 * @property int $site_id
 */
class TestModel extends \Illuminate\Database\Eloquent\Model
{
    use HasMultisite;
}
