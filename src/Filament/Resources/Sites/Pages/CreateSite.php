<?php

namespace Zoker\FilamentMultisite\Filament\Resources\Sites\Pages;

use Filament\Resources\Pages\CreateRecord;
use Zoker\FilamentMultisite\Filament\Resources\Sites\SiteResource;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
