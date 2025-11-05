<?php

namespace Zoker\FilamentMultisite\Filament\Actions;

use Filament\Actions\SelectAction;
use Zoker\FilamentMultisite\Models\Site;

class SiteSwitcher extends SelectAction
{
    public static function getDefaultName(): ?string
    {
        return 'activeSiteId';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Site');

        $this->options(Site::all()->mapWithKeys(fn (Site $site) => [$site->id => $site->name . ' (' . $site->locale . ')']));
    }
}
