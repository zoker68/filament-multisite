<?php

namespace Zoker\FilamentMultisite\DTO;

use Zoker\FilamentMultisite\Models\Site;

final class SitePickerItem
{
    public function __construct(
        public readonly Site $site,
        public readonly string $url,
        public readonly bool $isActive
    ) {}
}
