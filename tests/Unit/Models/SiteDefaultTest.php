<?php

namespace Zoker\FilamentMultisite\Tests\Unit\Models;

use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;

class SiteDefaultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Site::truncate();
    }

    public function test_only_one_site_can_be_default(): void
    {
        $a = $this->createActiveSite(['code' => 'a', 'prefix' => null, 'is_default' => true]);
        $b = $this->createActiveSite(['code' => 'b', 'prefix' => 'ru', 'is_default' => true]);

        // Flagging b default clears the flag on a.
        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);
        $this->assertSame(1, Site::where('is_default', true)->count());
    }

    public function test_get_default_returns_the_flagged_site(): void
    {
        $this->createActiveSite(['code' => 'a', 'prefix' => 'ru', 'is_default' => false]);
        $default = $this->createActiveSite(['code' => 'b', 'prefix' => null, 'is_default' => true]);

        $this->assertTrue(Site::getDefault()->is($default));
        $this->assertTrue(SiteManager::getDefaultSite()->is($default));
    }

    public function test_get_default_is_null_when_none_flagged(): void
    {
        $this->createActiveSite(['code' => 'a', 'is_default' => false]);

        $this->assertNull(Site::getDefault());
    }

    public function test_setting_default_via_update_clears_the_previous_default(): void
    {
        $a = $this->createActiveSite(['code' => 'a', 'prefix' => null, 'is_default' => true]);
        $b = $this->createActiveSite(['code' => 'b', 'prefix' => 'ru', 'is_default' => false]);

        // The real path is the SiteResource ToggleColumn (an update, not a create).
        $b->update(['is_default' => true]);

        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);
        $this->assertSame(1, Site::where('is_default', true)->count());
    }

    public function test_the_last_default_cannot_be_unset(): void
    {
        $a = $this->createActiveSite(['code' => 'a', 'is_default' => true]);

        $a->update(['is_default' => false]);

        // Exactly one default must remain — turning the only one off is a no-op.
        $this->assertTrue($a->fresh()->is_default);
    }

    public function test_deleting_the_default_reassigns_it_to_another_site(): void
    {
        $a = $this->createActiveSite(['code' => 'a', 'prefix' => null, 'is_default' => true]);
        $b = $this->createActiveSite(['code' => 'b', 'prefix' => 'ru', 'is_default' => false]);

        $a->delete();

        $this->assertTrue($b->fresh()->is_default);
        $this->assertSame(1, Site::where('is_default', true)->count());
    }

    public function test_get_current_site_falls_back_to_the_default_site(): void
    {
        $this->createActiveSite(['code' => 'plain', 'prefix' => null, 'is_default' => false]);
        $ru = $this->createActiveSite(['code' => 'ru', 'prefix' => 'ru', 'is_default' => true]);

        // No request context → the fallback prefers the flagged default over the
        // unprefixed site (old behaviour returned the prefix-null site).
        $this->assertTrue($ru->is(SiteManager::getCurrentSite()));
    }
}
