<?php

namespace Zoker\FilamentMultisite\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Zoker\FilamentMultisite\Models\Site;

class SiteChanged
{
    use Dispatchable;

    public function __construct(
        public Site $site,
        public ?Site $previousSite = null
    ) {}
}
