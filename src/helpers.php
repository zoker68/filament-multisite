<?php

use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;

function multisite_route(string $name, mixed $parameters = [], bool $absolute = true, ?Site $site = null, ?string $locale = null): string
{
    $site ??= SiteManager::getCurrentSite();

    $locale ??= $site->locale;

    $parameters = Arr::wrap($parameters);

    if ($site->prefix) {
        $parameters['multisite_prefix'] = $site->prefix;
    }

    if ($locale && $site->prefix && Route::has('multisite.' . $locale . '.' . $name)) {
        return route('multisite.' . $locale . '.' . $name, $parameters, $absolute);
    } elseif ($locale && Route::has($locale . '.' . $name)) {
        return route($locale . '.' . $name, $parameters, $absolute);
    }

    if ($site->prefix && Route::has('multisite.' . $site->locale . '.' . $name)) {
        return route('multisite.' . $site->locale . '.' . $name, $parameters, $absolute);
    } elseif (Route::has($site->locale . '.' . $name)) {
        return route($site->locale . '.' . $name, $parameters, $absolute);
    }

    if ($site->prefix && Route::has('multisite.' . $name)) {
        return route('multisite.' . $name, $parameters, $absolute);
    }

    if (! $site->prefix) {
        $nameWithoutLocalePrefix = ltrim($name, 'multisite.');
        if (Route::has($nameWithoutLocalePrefix) && $nameWithoutLocalePrefix != $name) {
            return route($nameWithoutLocalePrefix, $parameters, $absolute);
        }
    }

    return route($name, $parameters, $absolute);
}

function currentSite(): Site
{
    return SiteManager::getCurrentSite();
}
