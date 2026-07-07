<?php

namespace Zoker\FilamentMultisite\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Zoker\FilamentMultisite\Events\SiteChanged;
use Zoker\FilamentMultisite\Models\Site;

class SiteManager
{
    protected ?Site $currentSite = null;

    /**
     * Set the current site.
     *
     * @param  Site|null  $site  The site to set as current.
     *
     * @throws InvalidArgumentException If the site is not found or is inactive.
     */
    public function setCurrentSite(?Site $site): void
    {
        $original = $this->currentSite;

        if (! $site || ! $site->is_active) {
            $this->siteNotFound();
        }

        $this->currentSite = $site;

        if ($original?->id !== $site->id) {
            SiteChanged::dispatch($site, $original);
        }

        App::setLocale($site->locale);
        Config::set('app.locale', $site->locale);
        if (! empty($site->domain)) {
            Config::set('app.url', $site->hostWithScheme);
        }

        if ($site->prefix) {
            URL::defaults(['multisite_prefix' => $site->prefix]);
        }
    }

    /**
     * Set the current site by ID.
     *
     * @param  int  $id  The ID of the site to set as current.
     *
     * @throws InvalidArgumentException If the site is not found or is inactive.
     */
    public function setCurrentSiteById(int $id): void
    {
        $site = Site::find($id);

        $this->setCurrentSite($site);
    }

    /**
     * Set the current site by request.
     *
     * @param  Request  $request  The request to set the current site for.
     *
     * @throws InvalidArgumentException If the site is not found or is inactive.
     */
    public function setCurrentSiteByRequest(Request $request): void
    {
        [$domain, $prefix] = $this->extractDomainAndPrefix($request);

        $sites = Site::getForDomain($domain);
        if ($sites->isEmpty()) {
            $this->siteNotFound();
        }

        $activeSite = $sites->firstWhere('prefix', $prefix) ?? $sites->firstWhere('prefix', null);

        $this->setCurrentSite($activeSite);
    }

    /**
     * Extract domain and prefix from request.
     * For Livewire requests, uses Referer header to get original URL.
     *
     * @return array{0: string|null, 1: string|null}
     */
    protected function extractDomainAndPrefix(Request $request): array
    {
        if ($this->isLivewireRequest($request)) {
            return $this->extractFromReferer($request);
        }

        return [
            $this->getDomain($request->getHost()),
            $request->segment(1),
        ];
    }

    /**
     * Check if the request is a Livewire update request.
     */
    protected function isLivewireRequest(Request $request): bool
    {
        return $request->is('livewire/update', 'livewire/*')
            || $request->hasHeader('X-Livewire');
    }

    /**
     * Extract domain and prefix from Referer header.
     *
     * @return array{0: string|null, 1: string|null}
     */
    protected function extractFromReferer(Request $request): array
    {
        $referer = $request->header('Referer');

        if (! $referer) {
            return [
                $this->getDomain($request->getHost()),
                null,
            ];
        }

        $parsed = parse_url($referer);
        $host = $parsed['host'] ?? $request->getHost();
        $path = $parsed['path'] ?? '';
        $segments = array_values(array_filter(explode('/', $path)));
        $prefix = $segments[0] ?? null;

        return [
            $this->getDomain($host),
            $prefix,
        ];
    }

    public function getCurrentSite(): Site
    {
        if (! $this->currentSite) {
            $defaultDomain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: null;

            // One query, resolve in memory (there are few sites): the flagged
            // default first, then an unprefixed site on the app.url host / no
            // domain, then any active site.
            $activeSites = Site::active()->get();

            $fallbackSite = $activeSites->first(fn (Site $site) => $site->is_default)
                ?? $activeSites->first(fn (Site $site) => $site->prefix === null
                    && ($site->domain === $defaultDomain || $site->domain === null))
                ?? $activeSites->first();

            if (! $fallbackSite) {
                throw new InvalidArgumentException('Site not found');
            }

            $this->setCurrentSite($fallbackSite);
        }

        return $this->currentSite;
    }

    /**
     * The default (original) site of a group — defaults to the current site's group.
     */
    public function getDefaultSite(?Site $forSite = null): ?Site
    {
        $forSite ??= $this->getCurrentSite();

        return Site::getDefaultForGroup($forSite->site_group_id);
    }

    public function getCurrentSiteLocale(): string
    {
        return $this->getCurrentSite()->locale;
    }

    public function getCurrentLocale(): string
    {
        return $this->getCurrentSiteLocale();
    }

    public function getCurrentSiteId(): int
    {
        return $this->getCurrentSite()->id;
    }

    /**
     * Normalize the host for data in the Site model.
     *
     * @param  string  $host  The host to normalize.
     * @return string|null The normalized host.
     */
    private function getDomain(string $host): ?string
    {
        $url = parse_url(config('app.url'));
        $defaultHost = $url['host'] ?? null;

        if ($defaultHost == $host) {
            return null;
        }

        return $host;
    }

    public function siteNotFound(): void
    {
        throw new InvalidArgumentException('Site not found');
    }
}
