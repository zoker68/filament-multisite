<?php

namespace Zoker\FilamentMultisite\Tests\Unit\Models;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;

class InstalledDefaultSiteTest extends TestCase
{
    public function test_the_installed_default_site_is_flagged_as_default(): void
    {
        // create_sites_table seeds the 'default' (prefix = null) site on install;
        // the add_is_default migration backfills it as the default site.
        $default = Site::where('code', 'default')->first();

        $this->assertNotNull($default);
        $this->assertTrue($default->is_default);
        $this->assertNull($default->prefix);
    }
}
