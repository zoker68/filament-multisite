<?php

namespace Zoker\FilamentMultisite\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Zoker\FilamentMultisite\Http\Middleware\SetLocaleMiddleware;
use Zoker\FilamentMultisite\Models\Site;

class FilamentMultisiteRouteServiceProvider extends ServiceProvider
{
    private static ?array $routeTranslatableLocales = null;

    private static ?array $multisiteAvailablePrefixes = null;

    const string TRANSLATABLE_LOCALES_CACHE_KEY = 'multisite::translatable-locales';

    const string MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY = 'multisite::available-prefixes';

    public function boot(): void
    {
        Route::macro('multisite', function (callable $routes) {
            Route::middleware(SetLocaleMiddleware::class)->group(function () use ($routes) {

                $availablePrefixes = FilamentMultisiteRouteServiceProvider::getMultisiteAvailablePrefixes();
                if (count($availablePrefixes)) {
                    Route::prefix('{multisite_prefix}')
                        ->name('multisite.')
                        ->whereIn('multisite_prefix', $availablePrefixes)
                        ->group($routes);
                }

                $routes(); // Normal Routes without multisite prefix
            });
        });

        Route::macro('translated', function (callable $routes) {
            Route::middleware(SetLocaleMiddleware::class)->group(function () use ($routes) {
                foreach (FilamentMultisiteRouteServiceProvider::getRouteTranslatableLocales() as $locale) {
                    app()->setLocale($locale);

                    Route::name($locale . '.')->group($routes);
                }
            });
        });

    }

    public static function getRouteTranslatableLocales(): array
    {
        if (! self::$routeTranslatableLocales) {
            self::setRouteTranslatableLocales();
        }

        return self::$routeTranslatableLocales;
    }

    private static function setRouteTranslatableLocales(): void
    {
        self::$routeTranslatableLocales = cache()->remember(
            self::TRANSLATABLE_LOCALES_CACHE_KEY,
            60 * 60 * 3,
            fn () => Site::active()->get()->pluck('locale')->unique()->toArray() ?? []
        );
    }

    public static function getMultisiteAvailablePrefixes(): ?array
    {
        if (! self::$multisiteAvailablePrefixes) {
            self::setMultisiteAvailablePrefixes();
        }

        return self::$multisiteAvailablePrefixes;
    }

    private static function setMultisiteAvailablePrefixes(): void
    {
        self::$multisiteAvailablePrefixes = cache()->remember(
            self::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY,
            60 * 60 * 3,
            fn () => Site::active()->pluck('prefix')->unique()->filter(fn ($prefix) => filled($prefix))->toArray() ?? []
        );
    }
}
