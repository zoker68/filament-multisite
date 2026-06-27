<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Tests\Unit\DTO;

use Zoker\FilamentMultisite\DTO\SitePickerItem;
use Zoker\FilamentMultisite\Events\SiteChanged;
use Zoker\FilamentMultisite\Tests\TestCase;

class SitePickerItemTest extends TestCase
{
    public function test_it_exposes_its_readonly_properties(): void
    {
        $site = $this->createActiveSite();

        $item = new SitePickerItem($site, 'https://a.test', true);

        $this->assertTrue($site->is($item->site));
        $this->assertEquals('https://a.test', $item->url);
        $this->assertTrue($item->isActive);
    }

    public function test_site_changed_event_carries_current_and_previous_sites(): void
    {
        $current = $this->createActiveSite();
        $previous = $this->createActiveSite();

        $event = new SiteChanged($current, $previous);
        $this->assertTrue($current->is($event->site));
        $this->assertTrue($previous->is($event->previousSite));

        $withoutPrevious = new SiteChanged($current);
        $this->assertNull($withoutPrevious->previousSite);
    }
}
