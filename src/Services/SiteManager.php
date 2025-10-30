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
            throw new InvalidArgumentException('Site not found');
        }

        $this->currentSite = $site;

        if ($original?->id !== $site->id) {
            SiteChanged::dispatch($site, $original);
        }

        App::setLocale($site->locale);
        Config::set('app.locale', $site->locale);

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
        $domain = $this->getDomain($request->getHost());
        $prefix = $request->segment(1);

        $sites = Site::getForDomain($domain);

        $activeSite = $sites->firstWhere('prefix', $prefix) ?? $sites->firstWhere('prefix', null);

        $this->setCurrentSite($activeSite);
    }

    public function getCurrentSite(): ?Site
    {
        return $this->currentSite;
    }

    /**
     * Normalize the host for data in the Site model.
     *
     * @param  string  $host  The host to normalize.
     * @return string|null The normalized host.
     */
    private function getDomain(string $host): ?string
    {
        $defaultHost = parse_url(config('app.url'))['host'];

        if ($defaultHost == $host) {
            return null;
        }

        return $host;
    }
}
