<?php

namespace Zoker\FilamentMultisite\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteRouteServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../tests/database/migrations');

        // Clear the static properties before each test
        $reflection = new \ReflectionClass(FilamentMultisiteRouteServiceProvider::class);
        $translatableLocales = $reflection->getProperty('routeTranslatableLocales');
        $translatableLocales->setAccessible(true);
        $translatableLocales->setValue(null);

        $availablePrefixes = $reflection->getProperty('multisiteAvailablePrefixes');
        $availablePrefixes->setAccessible(true);
        $availablePrefixes->setValue(null);

        require_once __DIR__ . '/../src/helpers.php';

        cache()->forget(FilamentMultisiteRouteServiceProvider::TRANSLATABLE_LOCALES_CACHE_KEY);
        cache()->forget(FilamentMultisiteRouteServiceProvider::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Zoker\FilamentMultisite\Providers\FilamentMultisiteServiceProvider::class,
            FilamentMultisiteRouteServiceProvider::class,
        ];
    }

    protected function createSite(array $attributes = []): Site
    {
        return Site::factory()->create($attributes);
    }

    protected function createActiveSite(array $attributes = []): Site
    {
        return $this->createSite(array_merge(['is_active' => true], $attributes));
    }

    public function registerTestMultisiteRoutes()
    {
        // Set up test routes
        Route::multisite(function () {
            Route::get('test', function () {
                return response()->json([
                    'locale' => app()->getLocale(),
                    'prefix' => SiteManager::getCurrentSite()->prefix,
                ]);
            })->name('test.route');
        });
    }
}
