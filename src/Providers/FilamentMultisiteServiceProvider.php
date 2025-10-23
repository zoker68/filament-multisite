<?php

namespace Zoker\FilamentMultisite\Providers;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentMultisiteServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-multisite')
            ->hasMigrations([
                'create_sites_table',
            ]);
    }
}
