<?php

namespace Zoker\FilamentMultisite\Filament\Resources\SiteGroups\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Zoker\FilamentMultisite\Filament\Resources\SiteGroups\SiteGroupResource;

class ListSiteGroups extends ListRecords
{
    protected static string $resource = SiteGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
