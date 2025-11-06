<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Zoker\FilamentMultisite\Models\Site;

final class Multisite implements Plugin
{
    public static function make(): self
    {
        return new self;
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
