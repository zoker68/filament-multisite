<?php

namespace Zoker\FilamentMultisite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Zoker\FilamentMultisite\Database\Factories\SiteFactory;
use Zoker\FilamentMultisite\Observers\SiteObserver;

/**
 * @property string $code
 * @property string $name
 * @property ?string $domain
 * @property ?string $prefix
 * @property string $locale
 * @property bool $is_active
 */
#[ObservedBy([SiteObserver::class])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    const string SITES_FOR_DOMAIN_CACHE_KEY = 'multisite::sites_for_domain.';

    protected $fillable = ['code', 'name', 'domain', 'prefix', 'locale', 'is_active'];

    #[Scope] // @phpstan-ignore-line
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): SiteFactory
    {
        return SiteFactory::new();
    }

    /**
     * @return Collection<int, Site>|null
     */
    public static function getForDomain(?string $domain): ?Collection
    {
        return cache()->rememberForever(self::SITES_FOR_DOMAIN_CACHE_KEY . $domain, fn () => Site::where('domain', $domain)->get());
    }

    /**
     * @return array<string>
     */
    public static function getLocalesForFilament(): array
    {
        return self::pluck('locale')->unique()->toArray();
    }

    public function clearCache(): void
    {
        cache()->forget(self::SITES_FOR_DOMAIN_CACHE_KEY . $this->domain);
    }
}
