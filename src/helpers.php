<?php

function multisite_route(string $name, mixed $parameters = [], bool $absolute = true, ?string $locale = null): string
{
    $currentSite = app('currentSite');

    if ($locale) {
        return route('multisite.' . $locale . '.' . $name, $parameters, $absolute);
    }

    if ($currentSite->prefix && Route::has('multisite.' . $currentSite->locale . '.' . $name)) {
        return route('multisite.' . $currentSite->locale . '.' . $name, $parameters, $absolute);
    } elseif (Route::has($currentSite->locale . '.' . $name)) {
        return route($currentSite->locale . '.' . $name, $parameters, $absolute);
    }

    if ($currentSite->prefix && Route::has('multisite.' . $name)) {
        return route('multisite.' . $name, $parameters, $absolute);
    }

    return route($name, $parameters, $absolute);
}
