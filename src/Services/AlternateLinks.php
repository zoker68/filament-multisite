<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Services;

use Illuminate\Support\Collection;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;

class AlternateLinks
{
    /** @var array<int, array{site: Site, url: string}> */
    protected static array $links = [];

    protected static ?string $canonicalUrl = null;

    protected static bool $initialized = false;

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
