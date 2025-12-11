<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Traits;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;

trait HasMultisite
{
    protected static function bootHasMultisite(): void
    {
        static::addGlobalScope('multisite', function (Builder $query) {
            $currentSite = Filament::isServing()
                ? FilamentSiteManager::getCurrentSite()
                : SiteManager::getCurrentSite();
            $query->where('site_id', $currentSite->id);
        });
    }

    public function site(): BelongsTo // @phpstan-ignore-line
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForSite(Builder $query, int|Site $site): Builder
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        return $query->withoutGlobalScope('multisite')
            ->where('site_id', $siteId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAllSites(Builder $query): Builder
    {
        return $query->withoutGlobalScope('multisite');
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<int>  $siteIds
     * @return Builder<self>
     */
    public function scopeForSites(Builder $query, array $siteIds): Builder
    {
        return $query->withoutGlobalScope('multisite')
            ->whereIn('site_id', $siteIds);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeExceptSite(Builder $query, int|Site $site): Builder
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        return $query->withoutGlobalScope('multisite')
            ->where('site_id', '!=', $siteId);
    }

    /**
     * @return $this
     */
    public function setSite(int|Site $site): self
    {
        $this->site_id = $site instanceof Site ? $site->id : $site;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createForCurrentSite(array $attributes = []): self
    {
        $currentSite = Filament::isServing()
            ? FilamentSiteManager::getCurrentSite()
            : SiteManager::getCurrentSite();

        $attributes['site_id'] = $currentSite->id;

        return self::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createForSite(int|Site $site, array $attributes = []): self
    {
        $siteId = $site instanceof Site ? $site->id : $site;
        $attributes['site_id'] = $siteId;

        return self::create($attributes);
    }
}
