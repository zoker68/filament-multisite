<?php

namespace Zoker\FilamentMultisite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Zoker\FilamentMultisite\Database\Factories\SiteFactory;
use Zoker\FilamentMultisite\Observers\SiteObserver;

/**
 * @property ?int $site_group_id
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

    const string SITES_FOR_GROUP_CACHE_KEY = 'multisite::sites_for_group.';

    const string USING_LOCALES_CACHE_KEY = 'multisite::using_locales';

    protected $fillable = ['site_group_id', 'code', 'name', 'label', 'domain', 'prefix', 'locale', 'is_active', 'is_default'];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /** @var array<string> */
    private static ?array $usingLocales = null;

    /** @var array<string, Collection<int, Site>> */
    private static array $sitesForGroup = [];

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

    #[Scope] // @phpstan-ignore-line
    protected function inGroup(Builder $query, int|Site|null $group): Builder
    {
        return $query->where('site_group_id', $group instanceof Site ? $group->site_group_id : $group);
    }

    #[Scope] // @phpstan-ignore-line
    protected function default(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * @return BelongsTo<SiteGroup, $this>
     */
    public function group(): BelongsTo // @phpstan-ignore-line
    {
        return $this->belongsTo(SiteGroup::class, 'site_group_id');
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
     * Active sites on a domain — used only by the request resolver to pick the current
     * site by host + prefix (runs once per request over an indexed column, so it is
     * not cached; grouping/alternates use getForGroup instead).
     *
     * @return Collection<int, Site>
     */
    public static function getForDomain(?string $domain): Collection
    {
        return Site::query()->active()->where('domain', $domain)->get();
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
     * Active sites in the same group — the alternates of each other for hreflang
     * and the site switcher. Grouping is explicit (site_group_id), independent of
     * domain. Cached per group (no TTL; invalidated by SiteObserver); the static
     * memo is keyed by group so different groups in one request don't clash.
     *
     * @return Collection<int, Site>
     */
    public static function getForGroup(int|Site $site): Collection
    {
        $groupId = $site instanceof Site ? $site->site_group_id : $site;
        $key = (string) ($groupId ?? 'null');

        if (isset(self::$sitesForGroup[$key])) {
            return self::$sitesForGroup[$key];
        }

        $cacheKey = self::SITES_FOR_GROUP_CACHE_KEY . $key;

        if (cache()->has($cacheKey)) {
            return self::$sitesForGroup[$key] = Site::hydrate(cache()->get($cacheKey));
        }

        $sites = Site::query()->active()->inGroup($groupId)->get();
        cache()->put($cacheKey, $sites->map->getAttributes()->toArray());

        return self::$sitesForGroup[$key] = $sites;
    }

    /**
     * The "original" site of a group — content is authored there and translated out
     * of it. Exactly one per group (enforced by SiteObserver).
     */
    public static function getDefaultForGroup(?int $groupId): ?Site
    {
        return static::query()->inGroup($groupId)->default()->first();
    }

    /**
     * Any default site — only for the degenerate no-request fallback. Prefer
     * getDefaultForGroup() for group-scoped needs.
     */
    public static function getDefault(): ?Site
    {
        return static::query()->default()->first();
    }

    public function clearCache(): void
    {
        cache()->forget(self::SITES_FOR_GROUP_CACHE_KEY . ($this->site_group_id ?? 'null'));
        cache()->forget(self::USING_LOCALES_CACHE_KEY);

        self::$sitesForGroup = [];
    }
}
