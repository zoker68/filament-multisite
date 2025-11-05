<?php

namespace Zoker\FilamentMultisite;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Zoker\FilamentMultisite\Models\Site;

class Multisite implements Plugin
{
    public static function make()
    {
        return new static;
    }

    public function getId(): string
    {
        return 'filament-multisite';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(in: __DIR__ . '/../src/Filament/Resources', for: 'Zoker\\FilamentMultisite\\Filament\\Resources');

        $panel->plugin(SpatieTranslatablePlugin::make()->defaultLocales(Site::getLocalesForFilament())->persist());
    }

    public function boot(Panel $panel): void {}
}
