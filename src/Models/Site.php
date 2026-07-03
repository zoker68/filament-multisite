<?php

namespace Zoker\FilamentMultisite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Zoker\FilamentMultisite\Database\Factories\SiteFactory;
use Zoker\FilamentMultisite\Observers\SiteObserver;

/**
 * @property string $code
 * @property string $name
 * @property ?string $label
 * @property ?string $domain
 * @property ?string $prefix
 * @property string $locale
 * @property bool $is_active
 * @property bool $is_default
 * @property string $host_with_scheme
 * @property string $url
 */
#[ObservedBy([SiteObserver::class])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    const string SITES_FOR_DOMAIN_CACHE_KEY = 'multisite::sites_for_domain.';

    const string USING_LOCALES_CACHE_KEY = 'multisite::using_locales';

    protected $fillable = ['code', 'name', 'label', 'domain', 'prefix', 'locale', 'is_active', 'is_default'];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /** @var array<string> */
    private static ?array $usingLocales = null;

    /** @var Collection<int, Site> */
    private static ?Collection $sitesForDomain = null;

    public static function setUsingLocales(): void
    {
        if (cache()->has(self::USING_LOCALES_CACHE_KEY)) {
            self::$usingLocales = cache()->get(self::USING_LOCALES_CACHE_KEY);

            return;
        }

        if (! \Schema::hasTable((new Site)->getTable())) {
            self::$usingLocales = [config('app.locale')];

            return;
        }

        self::$usingLocales = self::pluck('locale')->unique()->toArray();
        cache()->put(self::USING_LOCALES_CACHE_KEY, self::$usingLocales);
    }

    #[Scope] // @phpstan-ignore-line
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function hostWithScheme(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->domain ? ('https://' . $this->domain) : config('app.url')
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $this->hostWithScheme . (filled($this->prefix) ? '/' . $this->prefix : ''),
        );
    }

    protected static function newFactory(): SiteFactory
    {
        return SiteFactory::new();
    }

    /**
     * @return Collection<int, Site>
     */
    public static function getForDomain(?string $domain): Collection
    {
        if (! self::$sitesForDomain) {
            $cacheKey = self::SITES_FOR_DOMAIN_CACHE_KEY . $domain;
            if (cache()->has($cacheKey)) {
                self::$sitesForDomain = Site::hydrate(cache()->get($cacheKey));
            } else {
                self::$sitesForDomain = Site::query()->active()->where('domain', $domain)->get();
                cache()->put($cacheKey, self::$sitesForDomain->map->getAttributes()->toArray());
            }
        }

        return self::$sitesForDomain;
    }

    /**
     * @return array<string>
     */
    public static function getUsingLocales(): array
    {
        if (! self::$usingLocales) {
            self::setUsingLocales();
        }

        return self::$usingLocales;
    }

    /**
     * The single "original" site content is authored on and translated out of.
     */
    public static function getDefault(): ?Site
    {
        return static::query()->where('is_default', true)->first();
    }

    public function clearCache(): void
    {
        cache()->forget(self::SITES_FOR_DOMAIN_CACHE_KEY . $this->domain);
        cache()->forget(self::USING_LOCALES_CACHE_KEY);
    }
}
