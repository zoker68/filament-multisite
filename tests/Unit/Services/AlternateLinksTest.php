<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Tests\Unit\Services;

use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Services\AlternateLinks;
use Zoker\FilamentMultisite\Tests\TestCase;

class AlternateLinksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // AlternateLinks keeps its state in static properties.
        AlternateLinks::clear();
    }

    public function test_it_is_not_initialized_by_default(): void
    {
        $this->assertFalse(AlternateLinks::isInitialized());
    }

    public function test_set_marks_as_initialized_and_keys_links_by_site_id(): void
    {
        $site1 = $this->createActiveSite(['locale' => 'en']);
        $site2 = $this->createActiveSite(['locale' => 'ru']);

        AlternateLinks::set([
            ['site' => $site1, 'url' => 'https://a.test'],
            ['site' => $site2, 'url' => 'https://b.test'],
        ]);

        $this->assertTrue(AlternateLinks::isInitialized());
        $this->assertCount(2, AlternateLinks::get());
    }

    public function test_get_flags_the_current_site_as_active_and_exposes_locale(): void
    {
        $site1 = $this->createActiveSite(['locale' => 'en']);
        $site2 = $this->createActiveSite(['locale' => 'ru']);

        AlternateLinks::set([
            ['site' => $site1, 'url' => 'https://a.test'],
            ['site' => $site2, 'url' => 'https://b.test'],
        ]);

        SiteManager::setCurrentSite($site2);

        $links = AlternateLinks::get();

        $this->assertFalse($links[$site1->id]['isActive']);
        $this->assertTrue($links[$site2->id]['isActive']);
        $this->assertEquals('en', $links[$site1->id]['locale']);
        $this->assertEquals('https://b.test', $links[$site2->id]['url']);
    }

    public function test_clear_resets_state(): void
    {
        AlternateLinks::set([
            ['site' => $this->createActiveSite(), 'url' => 'https://a.test'],
        ]);
        AlternateLinks::setCanonicalUrl('https://canonical.test');

        AlternateLinks::clear();

        $this->assertFalse(AlternateLinks::isInitialized());
        $this->assertCount(0, AlternateLinks::get());
        $this->assertNull(AlternateLinks::getCanonicalUrl());
    }

    public function test_canonical_url_is_null_without_configured_site(): void
    {
        config(['multisite.canonical_site_id' => null]);

        AlternateLinks::set([
            ['site' => $this->createActiveSite(), 'url' => 'https://a.test'],
        ]);

        $this->assertNull(AlternateLinks::getCanonicalUrl());
    }

    public function test_canonical_url_resolves_from_configured_site(): void
    {
        $site1 = $this->createActiveSite();
        $site2 = $this->createActiveSite();

        AlternateLinks::set([
            ['site' => $site1, 'url' => 'https://a.test'],
            ['site' => $site2, 'url' => 'https://b.test'],
        ]);

        config(['multisite.canonical_site_id' => $site2->id]);

        $this->assertEquals('https://b.test', AlternateLinks::getCanonicalUrl());
    }

    public function test_explicitly_set_canonical_url_takes_precedence(): void
    {
        AlternateLinks::setCanonicalUrl('https://explicit.test');

        $this->assertEquals('https://explicit.test', AlternateLinks::getCanonicalUrl());
    }
}
