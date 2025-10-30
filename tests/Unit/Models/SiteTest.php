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
}
