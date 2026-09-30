<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Services;

use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteRouteServiceProvider;

class AlternateLinks
{
    /** @var array<int, array{site: Site, url: string}> */
    protected static array $links = [];

    protected static ?string $canonicalUrl = null;

    protected static bool $initialized = false;

    protected static bool $fallbackResolved = false;

    /**
     * @param  array<int, array{site: Site, url: string}>  $links
     */
    public static function set(array $links): void
    {
        static::$links = [];
        static::$canonicalUrl = null;
        static::$initialized = true;

        foreach ($links as $data) {
            static::$links[$data['site']->id] = [
                'site' => $data['site'],
                'url' => $data['url'],
            ];
        }
    }

    /**
     * @return Collection<int, array{site: Site, url: string, locale: string, isActive: bool, isDefault: bool}>
     */
    public static function get(): Collection
    {
        static::resolveFallback();

        $currentSiteId = SiteManager::getCurrentSiteId();

        return collect(static::$links)
            ->map(fn (array $item) => [
                'site' => $item['site'],
                'url' => $item['url'],
                'locale' => $item['site']->locale,
                'isActive' => $item['site']->id === $currentSiteId,
                'isDefault' => (bool) $item['site']->is_default,
            ]);
    }

    public static function isInitialized(): bool
    {
        return static::$initialized;
    }

    public static function clear(): void
    {
        static::$links = [];
        static::$canonicalUrl = null;
        static::$initialized = false;
        static::$fallbackResolved = false;
    }

    /**
     * Nobody called set() for this request: the sites of the current group get the same
     * route with the same parameters, for routes registered by Route::multisite() /
     * Route::translated() only.
     */
    protected static function resolveFallback(): void
    {
        if (static::$initialized || static::$fallbackResolved) {
            return;
        }

        static::$fallbackResolved = true;

        $route = request()->route();

        if (! $route instanceof Route
            || $route->isFallback
            || $route->getName() === null
            || ! FilamentMultisiteRouteServiceProvider::isMultisiteRoute($route)) {
            return;
        }

        $currentSite = SiteManager::getCurrentSiteOrNull();

        if ($currentSite === null) {
            return;
        }

        $parameters = Arr::except($route->parameters(), 'multisite_prefix');

        foreach (Site::getForGroup($currentSite) as $site) {
            $url = static::routeUrlForSite($route->getName(), $parameters, $site);

            if ($url !== null) {
                static::$links[$site->id] = ['site' => $site, 'url' => $url];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected static function routeUrlForSite(string $name, array $parameters, Site $site): ?string
    {
        $siteRouteName = multisite_route_name($name, $site);

        // A prefixed site without its multisite.* variant would get another site's URL.
        if ($siteRouteName === null || ($site->prefix && ! str_starts_with($siteRouteName, 'multisite.'))) {
            return null;
        }

        try {
            $url = multisite_route($name, $parameters, true, $site);
        } catch (UrlGenerationException) {
            return null;
        }

        // The prefix constraint is fixed at route registration: a site added later gets a 404 URL.
        $siteRoute = RouteFacade::getRoutes()->getByName($siteRouteName);

        return $siteRoute?->matches(Request::create($url), false) ? $url : null;
    }

    /**
     * Resolve the canonical URL: an explicitly set one wins, otherwise every page
     * is self-canonical (its own current-site URL). Cross-language canonicals are
     * intentionally never generated — they would drop translations from the index.
     */
    protected static function generateCanonicalUrl(): void
    {
        // An explicit per-page canonical (e.g. from an SEO/Meta block) wins.
        if (static::$canonicalUrl !== null) {
            return;
        }

        // Self-canonical: the current site's own URL, or the clean current request
        // URL (no query string) when no alternates were set (e.g. utility pages).
        static::$canonicalUrl = static::$links[SiteManager::getCurrentSiteId()]['url'] ?? url()->current();
    }

    public static function getCanonicalUrl(): ?string
    {
        static::resolveFallback();
        static::generateCanonicalUrl();

        return static::$canonicalUrl;
    }

    public static function setCanonicalUrl(string $url): void
    {
        // Ignore blank values so an empty per-page canonical does not overwrite
        // the resolved one (config / self).
        if (trim($url) === '') {
            return;
        }

        static::$canonicalUrl = $url;
    }
}
