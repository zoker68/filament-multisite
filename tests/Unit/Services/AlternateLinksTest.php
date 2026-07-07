<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Tests\Unit\Services;

use Illuminate\Support\Facades\Blade;
use Zoker\FilamentMultisite\Facades\SiteManager;
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
        // The explicitly set canonical is cleared (it no longer sticks).
        $this->assertNotEquals('https://canonical.test', AlternateLinks::getCanonicalUrl());
    }

    public function test_canonical_falls_back_to_the_current_url_when_no_links_are_set(): void
    {
        // A utility page that never calls set() still gets a self-canonical.
        $this->assertNotNull(AlternateLinks::getCanonicalUrl());
        $this->assertEquals(url()->current(), AlternateLinks::getCanonicalUrl());
    }

    public function test_canonical_url_defaults_to_the_current_site_self(): void
    {
        $site1 = $this->createActiveSite(['locale' => 'en']);
        $site2 = $this->createActiveSite(['locale' => 'ru']);

        AlternateLinks::set([
            ['site' => $site1, 'url' => 'https://a.test'],
            ['site' => $site2, 'url' => 'https://b.test'],
        ]);

        SiteManager::setCurrentSite($site2);

        // No configured/explicit canonical → self-canonical (current site's own URL),
        // so a translation is never dropped from the index by a cross-language canonical.
        $this->assertEquals('https://b.test', AlternateLinks::getCanonicalUrl());
    }

    public function test_get_exposes_the_is_default_flag(): void
    {
        $default = $this->createActiveSite(['is_default' => true]);
        $other = $this->createActiveSite(['is_default' => false]);

        AlternateLinks::set([
            ['site' => $default, 'url' => 'https://a.test'],
            ['site' => $other, 'url' => 'https://b.test'],
        ]);

        $links = AlternateLinks::get();

        $this->assertTrue($links[$default->id]['isDefault']);
        $this->assertFalse($links[$other->id]['isDefault']);
    }

    public function test_set_canonical_url_ignores_blank_and_keeps_self(): void
    {
        $site = $this->createActiveSite();
        AlternateLinks::set([['site' => $site, 'url' => 'https://a.test']]);
        SiteManager::setCurrentSite($site);

        // A blank per-page canonical must not overwrite the resolved (self) one.
        AlternateLinks::setCanonicalUrl('');

        $this->assertEquals('https://a.test', AlternateLinks::getCanonicalUrl());
    }

    public function test_explicitly_set_canonical_url_takes_precedence(): void
    {
        AlternateLinks::setCanonicalUrl('https://explicit.test');

        $this->assertEquals('https://explicit.test', AlternateLinks::getCanonicalUrl());
    }

    public function test_head_renders_self_canonical_even_for_a_single_site(): void
    {
        $site = $this->createActiveSite();
        AlternateLinks::set([['site' => $site, 'url' => 'https://a.test']]);
        SiteManager::setCurrentSite($site);

        // A single-item set (single site / an unlinked page) must still emit the
        // self-canonical — it is not gated behind the multi-site hreflang block.
        $html = Blade::render('<x-multisite::alternateLinksHead />');

        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('https://a.test', $html);
    }

    public function test_head_renders_x_default_for_the_default_site(): void
    {
        $default = $this->createActiveSite(['is_default' => true]);
        $other = $this->createActiveSite(['is_default' => false]);

        AlternateLinks::set([
            ['site' => $default, 'url' => 'https://a.test'],
            ['site' => $other, 'url' => 'https://b.test'],
        ]);

        $html = Blade::render('<x-multisite::alternateLinksHead />');

        $this->assertStringContainsString('hreflang="x-default"', $html);
    }
}
