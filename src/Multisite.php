<?php

namespace Zoker\FilamentMultisite;

use Filament\Contracts\Plugin;
use Filament\Panel;

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
    }

    public function boot(Panel $panel): void {}
}
