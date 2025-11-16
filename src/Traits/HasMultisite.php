<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;

trait HasMultisite
{
    /**
     * Boot the trait - adds global scope for multisite filtering.
     */
    protected static function bootHasMultisite(): void
    {
        static::addGlobalScope('multisite', function (Builder $query) {
            $currentSite = SiteManager::getCurrentSite();
            $query->where('site_id', $currentSite->id);
        });
    }

    /**
     * Get the site this model belongs to.
     */
    public function site(): BelongsTo // @phpstan-ignore-line
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     *
     * Get records for specific site without global scope.
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
     *
     * Get records for all sites without global scope.
     */
    public function scopeAllSites(Builder $query): Builder
    {
        return $query->withoutGlobalScope('multisite');
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<int>  $siteIds
     * @return Builder<self>
     *
     * Scope to get records for multiple sites.
     */
    public function scopeForSites(Builder $query, array $siteIds): Builder
    {
        return $query->withoutGlobalScope('multisite')
            ->whereIn('site_id', $siteIds);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     *
     * Scope to exclude records from specific site.
     */
    public function scopeExceptSite(Builder $query, int|Site $site): Builder
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        return $query->withoutGlobalScope('multisite')
            ->where('site_id', '!=', $siteId);
    }

    /**
     * Set the site for this model.
     *
     * @return $this
     */
    public function setSite(int|Site $site): self
    {
        $this->site_id = $site instanceof Site ? $site->id : $site;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return self
     *
     * Create a new model instance for the current site.
     */
    public static function createForCurrentSite(array $attributes = []): self
    {
        $attributes['site_id'] = SiteManager::getCurrentSite()->id;

        return self::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return self
     *
     * Create a new model instance for specific site.
     */
    public static function createForSite(int|Site $site, array $attributes = []): self
    {
        $siteId = $site instanceof Site ? $site->id : $site;
        $attributes['site_id'] = $siteId;

        return self::create($attributes);
    }
}
