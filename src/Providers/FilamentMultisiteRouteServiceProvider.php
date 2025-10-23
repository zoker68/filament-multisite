<?php

namespace Zoker\FilamentMultisite\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Zoker\FilamentMultisite\Http\Middleware\SetLocaleMiddleware;

class FilamentMultisiteRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::macro('multisite', function (callable $routes) {
            Route::middleware(SetLocaleMiddleware::class)->group(function () use ($routes) {

                // TODO: add Where for multisite_prefix
                Route::prefix('{multisite_prefix}')->name('multisite.')->group($routes);
                $routes();
            });
        });
    }
}
