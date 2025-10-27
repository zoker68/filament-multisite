<?php

namespace Zoker\FilamentMultisite\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Zoker\FilamentMultisite\Database\Factories\SiteFactory;
use Zoker\FilamentMultisite\Observers\SiteObserver;

#[ObservedBy([SiteObserver::class])]
class Site extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'domain', 'prefix', 'locale', 'is_active'];

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory()
    {
        return SiteFactory::new();
    }
}
