<?php

use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteRouteServiceProvider;

function multisite_route(string $name, mixed $parameters = [], bool $absolute = true, ?Site $site = null, ?string $locale = null): string
{
    $site ??= SiteManager::getCurrentSite();

    // A site on its own domain must produce absolute URLs on THAT host (route()
    // alone builds on the current request host). Build the relative path with the
    // normal logic, then prepend the site's own scheme+domain.
    if ($absolute && filled($site->domain)) {
        return rtrim($site->hostWithScheme, '/') . multisite_route($name, $parameters, false, $site, $locale);
    }

    // The name may come from an already-resolved route (request()->route()->getName()),
    // which on a prefixed site is "multisite.cart" and on a translated route
    // "{locale}.cart". Strip those macro-generated prefixes back to the base name —
    // the resolution below re-adds them per target site. Without this nothing matches,
    // and the final route() fallback lets URL::defaults() fill multisite_prefix with
    // the CURRENT site's prefix, so links for every other site point back to the
    // current one.
    $name = Str::chopStart($name, 'multisite.');
    $firstSegment = Str::before($name, '.');
    if ($firstSegment !== $name
        && in_array($firstSegment, FilamentMultisiteRouteServiceProvider::getRouteTranslatableLocales(), true)) {
        $name = Str::after($name, '.');
    }

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

    // No multisite/locale variant matched, so this is a plain route that does not use the
    // multisite_prefix segment. Drop it to avoid leaking it as a ?multisite_prefix query string.
    unset($parameters['multisite_prefix']);

    return route($name, $parameters, $absolute);
}

function currentSite(): Site
{
    return SiteManager::getCurrentSite();
}
