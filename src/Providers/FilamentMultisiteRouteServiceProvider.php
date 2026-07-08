<?php

namespace Zoker\FilamentMultisite\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Zoker\FilamentMultisite\Http\Middleware\MultisiteMiddleware;
use Zoker\FilamentMultisite\Models\Site;

class FilamentMultisiteRouteServiceProvider extends ServiceProvider
{
    /** @var array<string> */
    private static ?array $routeTranslatableLocales = null;

    /** @var array<string> */
    private static ?array $multisiteAvailablePrefixes = null;

    const string TRANSLATABLE_LOCALES_CACHE_KEY = 'multisite::translatable-locales';

    const string MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY = 'multisite::available-prefixes';

    public function boot(): void
    {
        // Site resolution must run on every web request — including Livewire's
        // `/livewire/update` endpoint, which lives on the `web` group but outside
        // the `Route::multisite()` macro. Registering it here means integrators
        // don't have to remember to append it in bootstrap/app.php; without it the
        // current site silently falls back to the default on Livewire requests.
        //
        // Must go through the HTTP kernel (not the router's pushMiddlewareToGroup):
        // the kernel re-syncs its own middleware-group definitions to the router on
        // bootstrap, which would otherwise clobber a router-only push. Laravel
        // de-duplicates middleware per route, so this is safe alongside the macro's
        // own MultisiteMiddleware.
        $this->app->make(HttpKernel::class)->appendMiddlewareToGroup('web', MultisiteMiddleware::class);

        Route::macro('multisite', function (\Closure $routes) {
            Route::middleware(MultisiteMiddleware::class)->group(function () use ($routes) {

                $availablePrefixes = FilamentMultisiteRouteServiceProvider::getMultisiteAvailablePrefixes();
                if (is_array($availablePrefixes) && count($availablePrefixes)) {
                    Route::prefix('{multisite_prefix}')
                        ->name('multisite.')
                        ->whereIn('multisite_prefix', $availablePrefixes)
                        ->group($routes);
                }

                $routes(); // Normal Routes without multisite prefix
            });
        });

        Route::macro('translated', function (\Closure $routes) {
            Route::middleware(MultisiteMiddleware::class)->group(function () use ($routes) {
                foreach (FilamentMultisiteRouteServiceProvider::getRouteTranslatableLocales() as $locale) {
                    app()->setLocale($locale);

                    Route::name($locale . '.')->group($routes);
                }
            });
        });

    }

    /** @return array<string> */
    public static function getRouteTranslatableLocales(): array
    {
        if (! self::$routeTranslatableLocales) {
            self::setRouteTranslatableLocales();
        }

        /** @var array<string> */
        return self::$routeTranslatableLocales;
    }

    private static function setRouteTranslatableLocales(): void
    {
        self::$routeTranslatableLocales = cache()->remember(
            self::TRANSLATABLE_LOCALES_CACHE_KEY,
            60 * 60 * 3,
            fn () => Site::active()->get()->pluck('locale')->unique()->toArray()
        );
    }

    /** @return array<string> */
    public static function getMultisiteAvailablePrefixes(): ?array
    {
        if (! self::$multisiteAvailablePrefixes) {
            self::setMultisiteAvailablePrefixes();
        }

        return self::$multisiteAvailablePrefixes;
    }

    private static function setMultisiteAvailablePrefixes(): void
    {
        if (! cache()->has(self::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY) && ! Schema::hasTable('sites')) {
            self::$multisiteAvailablePrefixes = [];

            return;
        }

        self::$multisiteAvailablePrefixes = cache()->remember(
            self::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY,
            60 * 60 * 3,
            fn () => Site::active()->pluck('prefix')->unique()->filter(fn ($prefix) => filled($prefix))->toArray()
        );
    }
}
