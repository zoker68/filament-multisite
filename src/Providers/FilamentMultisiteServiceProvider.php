<?php

namespace Zoker\FilamentMultisite\Providers;

use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentMultisiteServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('multisite')
            ->hasMigrations([
                'create_sites_table',
                'add_label_to_sites_table',
            ])
            ->hasViews('multisite')
            ->hasTranslations()
            ->hasConfigFile();
    }

    public function registeringPackage(): void
    {
        Blade::componentNamespace('Zoker\\FilamentMultisite\\View\\Components', 'multisite');
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
