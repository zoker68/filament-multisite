<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Services;

use Illuminate\Support\Collection;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;

class AlternateLinks
{
    /** @var array<int, array{site: Site, url: string, locale: string}> */
    protected static array $links = [];

    protected static ?string $canonicalUrl = null;

    protected static bool $initialized = false;

    /**
     * @param  array<int, array{site: Site, url: string}>  $links
     */
    public static function set(array $links): void
    {
        static::$links = [];
        static::$initialized = true;

        foreach ($links as $data) {
            static::$links[$data['site']->id] = [
                'site' => $data['site'],
                'url' => $data['url'],
            ];
        }
    }

    /**
     * @return Collection<int, array{site: Site, url: string, locale: string, isActive: bool}>
     */
    public static function get(): Collection
    {
        $currentSiteId = SiteManager::getCurrentSiteId();

        return collect(static::$links)
            ->map(fn (array $item) => [
                'site' => $item['site'],
                'url' => $item['url'],
                'locale' => $item['site']->locale,
                'isActive' => $item['site']->id === $currentSiteId,
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
    }

    /**
     * Generate canonical URL based on configuration
     */
    protected static function generateCanonicalUrl(): void
    {
        // If canonical URL is already set, don't overwrite it
        if (static::$canonicalUrl !== null) {
            return;
        }

        $canonicalSiteId = config('multisite.canonical_site_id');

        if (! $canonicalSiteId) {
            static::$canonicalUrl = null;

            return;
        }

        static::$canonicalUrl = static::$links[$canonicalSiteId]['url'] ?? null;
    }

    public static function getCanonicalUrl(): ?string
    {
        static::generateCanonicalUrl();

        return static::$canonicalUrl;
    }

    public static function setCanonicalUrl(string $url): void
    {
        static::$canonicalUrl = $url;
    }
}
