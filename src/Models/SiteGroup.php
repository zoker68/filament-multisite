<?php

namespace Zoker\FilamentMultisite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Zoker\FilamentMultisite\Database\Factories\SiteGroupFactory;

/**
 * A group of sites that are alternates/translations of each other (independent of
 * domain). hreflang, the site switcher and the per-group default site are scoped
 * to the group; domain stays a routing/hosting concern only.
 *
 * @property string $name
 * @property ?string $code
 */
class SiteGroup extends Model
{
    /** @use HasFactory<SiteGroupFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code'];

    /**
     * @return HasMany<Site, $this>
     */
    public function sites(): HasMany // @phpstan-ignore-line
    {
        return $this->hasMany(Site::class);
    }

    protected static function newFactory(): SiteGroupFactory
    {
        return SiteGroupFactory::new();
    }
}
