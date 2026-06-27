<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Tests\Unit\Observers;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteRouteServiceProvider;
use Zoker\FilamentMultisite\Tests\TestCase;

class SiteObserverTest extends TestCase
{
    private function primeCaches(): void
    {
        cache()->put(FilamentMultisiteRouteServiceProvider::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY, ['x']);
        cache()->put(FilamentMultisiteRouteServiceProvider::TRANSLATABLE_LOCALES_CACHE_KEY, ['x']);
    }

    private function assertCachesCleared(): void
    {
        $this->assertFalse(cache()->has(FilamentMultisiteRouteServiceProvider::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY));
        $this->assertFalse(cache()->has(FilamentMultisiteRouteServiceProvider::TRANSLATABLE_LOCALES_CACHE_KEY));
    }

    public function test_creating_a_site_clears_route_caches(): void
    {
        $this->primeCaches();

        Site::factory()->create();

        $this->assertCachesCleared();
    }

    public function test_saving_a_site_clears_route_caches(): void
    {
        $site = Site::factory()->create();
        $this->primeCaches();

        $site->update(['name' => 'Renamed']);

        $this->assertCachesCleared();
    }

    public function test_deleting_a_site_clears_route_caches(): void
    {
        $site = Site::factory()->create();
        $this->primeCaches();

        $site->delete();

        $this->assertCachesCleared();
    }
}
