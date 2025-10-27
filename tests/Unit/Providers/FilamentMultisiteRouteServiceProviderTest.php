<?php

namespace Zoker\FilamentMultisite\Tests\Unit\Providers;

use Illuminate\Support\Facades\Cache;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteRouteServiceProvider;
use Zoker\FilamentMultisite\Tests\TestCase;

class FilamentMultisiteRouteServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear the cache before each test
        Cache::forget(FilamentMultisiteRouteServiceProvider::TRANSLATABLE_LOCALES_CACHE_KEY);
        Cache::forget(FilamentMultisiteRouteServiceProvider::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY);
    }

    public function test_it_returns_translatable_locales()
    {
        $this->createActiveSite(['locale' => 'en']);
        $this->createActiveSite(['locale' => 'fr']);
        $this->createActiveSite(['locale' => 'fr']); // Duplicate to test uniqueness

        $locales = FilamentMultisiteRouteServiceProvider::getRouteTranslatableLocales();

        $this->assertCount(2, $locales);
        $this->assertContains('en', $locales);
        $this->assertContains('fr', $locales);
    }

    public function test_it_caches_translatable_locales()
    {
        $this->createActiveSite(['locale' => 'en']);

        // First call - should cache the result
        $firstCall = FilamentMultisiteRouteServiceProvider::getRouteTranslatableLocales();

        $this->assertTrue(cache()->has(FilamentMultisiteRouteServiceProvider::TRANSLATABLE_LOCALES_CACHE_KEY));
        $this->assertEquals($firstCall, cache()->get(FilamentMultisiteRouteServiceProvider::TRANSLATABLE_LOCALES_CACHE_KEY));
    }

    public function test_it_returns_available_prefixes()
    {
        $this->createActiveSite(['prefix' => 'en']);
        $this->createActiveSite(['prefix' => 'fr']);
        $this->createActiveSite(['prefix' => null]); // Should be included in available prefixes
        $this->createSite(['prefix' => 'de', 'is_active' => false]); // Inactive, should be excluded

        $prefixes = FilamentMultisiteRouteServiceProvider::getMultisiteAvailablePrefixes();

        $this->assertCount(2, $prefixes);
        $this->assertContains('en', $prefixes);
        $this->assertContains('fr', $prefixes);
        $this->assertNotContains(null, $prefixes);
        $this->assertNotContains('de', $prefixes);
    }

    public function test_it_caches_available_prefixes()
    {
        $this->createActiveSite(['prefix' => 'en']);

        // First call - should cache the result
        $firstCall = FilamentMultisiteRouteServiceProvider::getMultisiteAvailablePrefixes();

        $this->assertTrue(cache()->has(FilamentMultisiteRouteServiceProvider::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY));
        $this->assertEquals($firstCall, cache()->get(FilamentMultisiteRouteServiceProvider::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY));
    }
}
