<?php

use Zoker\FilamentMultisite\Models\Site;

function multisite_route(string $name, mixed $parameters = [], bool $absolute = true, ?Site $site = null, ?string $locale = null): string
{
    if (! isset($site) && app()->has('currentSite')) {
        $site = app('currentSite');
    }

    if ($locale && Route::has('multisite.' . $locale . '.' . $name)) {
        return route('multisite.' . $locale . '.' . $name, $parameters, $absolute);
    } elseif ($locale && Route::has($locale . '.' . $name)) {
        return route($locale . '.' . $name, $parameters, $absolute);
    }

    if (isset($site) && $site->prefix && Route::has('multisite.' . $site->locale . '.' . $name)) {
        return route('multisite.' . $site->locale . '.' . $name, $parameters, $absolute);
    } elseif (isset($site) && Route::has($site->locale . '.' . $name)) {
        return route($site->locale . '.' . $name, $parameters, $absolute);
    }

    if (isset($site) && $site->prefix && Route::has('multisite.' . $name)) {

        return route('multisite.' . $name, $parameters, $absolute);
    }

    return route($name, $parameters, $absolute);
}
