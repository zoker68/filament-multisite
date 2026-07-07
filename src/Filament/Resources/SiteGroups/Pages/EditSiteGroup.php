<?php

namespace Zoker\FilamentMultisite\Filament\Resources\SiteGroups\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Zoker\FilamentMultisite\Filament\Resources\SiteGroups\SiteGroupResource;

class EditSiteGroup extends EditRecord
{
    protected static string $resource = SiteGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
