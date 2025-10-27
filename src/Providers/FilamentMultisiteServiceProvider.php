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
            ])
            ->hasTranslations();
    }

    public function bootingPackage(): void
    {
        $this->loadHelpers();
    }

    private function loadHelpers(): void
    {
        require_once __DIR__ . '/../helpers.php';
    }
}
