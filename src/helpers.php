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
    // which carries the macro prefixes. The resolution below re-adds them per target site;
    // without stripping nothing matches and the final route() fallback lets URL::defaults()
    // fill multisite_prefix with the CURRENT site's prefix, so every site links back to it.
    $name = multisite_route_base_name($name);

    $parameters = Arr::wrap($parameters);

    if ($site->prefix) {
        $parameters['multisite_prefix'] = $site->prefix;
    }

    $routeName = multisite_route_name($name, $site, $locale) ?? $name;

    // No multisite/locale variant matched, so this is a plain route that does not use the
    // multisite_prefix segment. Drop it to avoid leaking it as a ?multisite_prefix query string.
    if ($routeName === $name) {
        unset($parameters['multisite_prefix']);
    }

    return route($routeName, $parameters, $absolute);
}

/**
 * The registered route multisite_route() builds for the site: its multisite/locale variant,
 * or the plain route. Null when none of them is registered.
 */
function multisite_route_name(string $name, Site $site, ?string $locale = null): ?string
{
    $name = multisite_route_base_name($name);
    $locale ??= $site->locale;

    $candidates = [];

    if ($locale) {
        if ($site->prefix) {
            $candidates[] = 'multisite.' . $locale . '.' . $name;
        }

        $candidates[] = $locale . '.' . $name;
    }

    if ($site->prefix) {
        $candidates[] = 'multisite.' . $site->locale . '.' . $name;
    }

    $candidates[] = $site->locale . '.' . $name;

    if ($site->prefix) {
        $candidates[] = 'multisite.' . $name;
    }

    $candidates[] = $name;

    foreach ($candidates as $candidate) {
        if (Route::has($candidate)) {
            return $candidate;
        }
    }

    return null;
}

/**
 * Strip the prefixes the Route::multisite() / Route::translated() macros add to a route name:
 * "multisite.cart", "ru.cart" and "multisite.ru.cart" all mean "cart".
 */
function multisite_route_base_name(string $name): string
{
    $name = Str::chopStart($name, 'multisite.');
    $firstSegment = Str::before($name, '.');

    if ($firstSegment !== $name
        && in_array($firstSegment, FilamentMultisiteRouteServiceProvider::getRouteTranslatableLocales(), true)) {
        return Str::after($name, '.');
    }

    return $name;
}

function currentSite(): Site
{
    return SiteManager::getCurrentSite();
}
