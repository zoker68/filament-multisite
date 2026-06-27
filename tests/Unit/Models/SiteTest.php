<?php

namespace Zoker\FilamentMultisite\Tests\Unit\Models;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;

class SiteTest extends TestCase
{
    public function test_it_can_create_a_site()
    {
        $site = $this->createSite([
            'domain' => 'example.com',
            'prefix' => 'eng',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Site::class, $site);
        $this->assertEquals('example.com', $site->domain);
        $this->assertEquals('eng', $site->prefix);
        $this->assertEquals('en', $site->locale);
        $this->assertTrue($site->is_active);
    }

    public function test_it_has_scope_active()
    {
        $activeSite = $this->createActiveSite();
        $inactiveSite = $this->createSite(['is_active' => false]);

        $activeSites = Site::active()->get();

        $this->assertTrue($activeSites->contains($activeSite));
        $this->assertFalse($activeSites->contains($inactiveSite));
    }

    public function test_host_with_scheme_uses_domain_when_present()
    {
        $site = $this->createSite(['domain' => 'shop.example.com']);

        $this->assertEquals('https://shop.example.com', $site->host_with_scheme);
    }

    public function test_host_with_scheme_falls_back_to_app_url_without_domain()
    {
        $site = $this->createSite(['domain' => null]);

        $this->assertEquals(config('app.url'), $site->host_with_scheme);
    }

    public function test_url_appends_prefix_when_present()
    {
        $site = $this->createSite(['domain' => 'shop.example.com', 'prefix' => 'eng']);

        $this->assertEquals('https://shop.example.com/eng', $site->url);
    }

    public function test_url_has_no_prefix_segment_when_prefix_is_empty()
    {
        $site = $this->createSite(['domain' => 'shop.example.com', 'prefix' => null]);

        $this->assertEquals('https://shop.example.com', $site->url);
    }

    public function test_get_using_locales_returns_distinct_locales()
    {
        Site::truncate();
        $this->createActiveSite(['locale' => 'en']);
        $this->createActiveSite(['locale' => 'ru']);
        $this->createActiveSite(['locale' => 'en']);

        $locales = Site::getUsingLocales();

        sort($locales);
        $this->assertEquals(['en', 'ru'], $locales);
    }

    public function test_get_for_domain_returns_only_active_sites_for_the_domain()
    {
        Site::truncate();
        $active = $this->createActiveSite(['domain' => 'a.example.com', 'prefix' => 'en']);
        $this->createSite(['domain' => 'a.example.com', 'prefix' => 'ru', 'is_active' => false]);
        $this->createActiveSite(['domain' => 'b.example.com']);

        $sites = Site::getForDomain('a.example.com');

        $this->assertCount(1, $sites);
        $this->assertTrue($sites->first()->is($active));
    }

    public function test_clear_cache_forgets_the_cached_keys()
    {
        $site = $this->createActiveSite(['domain' => 'a.example.com']);

        cache()->put(Site::SITES_FOR_DOMAIN_CACHE_KEY . 'a.example.com', 'x');
        cache()->put(Site::USING_LOCALES_CACHE_KEY, 'x');

        $site->clearCache();

        $this->assertFalse(cache()->has(Site::SITES_FOR_DOMAIN_CACHE_KEY . 'a.example.com'));
        $this->assertFalse(cache()->has(Site::USING_LOCALES_CACHE_KEY));
    }
}
