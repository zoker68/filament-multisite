<?php

namespace Zoker\FilamentMultisite\Tests\Unit\Models;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Models\SiteGroup;
use Zoker\FilamentMultisite\Tests\TestCase;

class SiteGroupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Site::truncate();
        SiteGroup::truncate();
    }

    public function test_get_for_group_returns_only_same_group_active_sites(): void
    {
        $groupA = SiteGroup::factory()->create();
        $groupB = SiteGroup::factory()->create();

        $a1 = Site::factory()->forGroup($groupA)->create(['code' => 'a1']);
        $a2 = Site::factory()->forGroup($groupA)->create(['code' => 'a2']);
        Site::factory()->forGroup($groupB)->create(['code' => 'b1']);          // other group
        Site::factory()->forGroup($groupA)->inactive()->create(['code' => 'a3']); // inactive

        $this->assertEqualsCanonicalizing(
            [$a1->id, $a2->id],
            Site::getForGroup($a1)->pluck('id')->all()
        );
    }

    public function test_get_for_group_is_cached_and_invalidated_on_write(): void
    {
        $group = SiteGroup::factory()->create();
        $a1 = Site::factory()->forGroup($group)->create(['code' => 'a1']);

        $key = Site::SITES_FOR_GROUP_CACHE_KEY . $group->id;

        Site::getForGroup($a1);
        $this->assertTrue(cache()->has($key));  // primed

        // A write to a site in the group must invalidate its cache (SiteObserver).
        Site::factory()->forGroup($group)->create(['code' => 'a2']);
        $this->assertFalse(cache()->has($key));
    }

    public function test_get_for_group_memo_is_keyed_per_group(): void
    {
        $groupA = SiteGroup::factory()->create();
        $groupB = SiteGroup::factory()->create();
        $a1 = Site::factory()->forGroup($groupA)->create(['code' => 'a1']);
        $b1 = Site::factory()->forGroup($groupB)->create(['code' => 'b1']);

        // Priming group A must NOT make group B return A's sites (the old static-memo
        // bug where the cache ignored its argument).
        Site::getForGroup($a1);

        $this->assertEqualsCanonicalizing([$b1->id], Site::getForGroup($b1)->pluck('id')->all());
    }

    public function test_default_is_unique_per_group(): void
    {
        $groupA = SiteGroup::factory()->create();
        $groupB = SiteGroup::factory()->create();

        $a1 = Site::factory()->forGroup($groupA)->create(['code' => 'a1', 'is_default' => true]);
        $b1 = Site::factory()->forGroup($groupB)->create(['code' => 'b1', 'is_default' => true]);
        $a2 = Site::factory()->forGroup($groupA)->create(['code' => 'a2', 'is_default' => true]); // A's new default

        $this->assertFalse($a1->fresh()->is_default);  // A's old default cleared
        $this->assertTrue($a2->fresh()->is_default);
        $this->assertTrue($b1->fresh()->is_default);    // B untouched
        $this->assertSame(1, Site::where('site_group_id', $groupA->id)->where('is_default', true)->count());
        $this->assertSame(1, Site::where('site_group_id', $groupB->id)->where('is_default', true)->count());
    }

    public function test_get_default_for_group_is_scoped(): void
    {
        $groupA = SiteGroup::factory()->create();
        $groupB = SiteGroup::factory()->create();
        $aDefault = Site::factory()->forGroup($groupA)->create(['code' => 'a1', 'is_default' => true]);
        Site::factory()->forGroup($groupB)->create(['code' => 'b1', 'is_default' => true]);

        $this->assertTrue(Site::getDefaultForGroup($groupA->id)->is($aDefault));
    }

    public function test_deleting_a_group_default_reassigns_within_the_group(): void
    {
        $groupA = SiteGroup::factory()->create();
        $a1 = Site::factory()->forGroup($groupA)->create(['code' => 'a1', 'prefix' => null, 'is_default' => true]);
        $a2 = Site::factory()->forGroup($groupA)->create(['code' => 'a2', 'prefix' => 'ru', 'is_default' => false]);

        $groupB = SiteGroup::factory()->create();
        $b1 = Site::factory()->forGroup($groupB)->create(['code' => 'b1', 'is_default' => true]);

        $a1->delete();

        $this->assertTrue($a2->fresh()->is_default);   // reassigned inside group A
        $this->assertTrue($b1->fresh()->is_default);   // group B untouched
    }
}
