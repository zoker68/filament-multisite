<?php

namespace Zoker\FilamentMultisite\Tests\Unit\Services;

use Illuminate\Support\Facades\Session;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Services\FilamentSiteManager;
use Zoker\FilamentMultisite\Tests\TestCase;

class FilamentSiteManagerTest extends TestCase
{
    private FilamentSiteManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new FilamentSiteManager;
        Session::flush();
    }

    public function test_it_sets_current_site()
    {
        $site = Site::factory()->create();

        $this->manager->setCurrentSite($site);

        $this->assertEquals($site->id, $this->manager->getCurrentSite()->id);
    }

    public function test_it_does_not_update_session_when_setting_same_site()
    {
        $site = Site::factory()->create();

        $this->manager->setCurrentSite($site);

        Session::spy();

        $this->manager->setCurrentSite($site);

        Session::shouldNotHaveReceived('put');
    }

    public function test_it_initializes_current_site_from_session()
    {
        $site = Site::factory()->create();
        Session::put(FilamentSiteManager::MULTISITE_FILAMENT_ACTIVE_SITE_ID_SESSION, $site->id);

        $manager = new FilamentSiteManager;

        $this->assertEquals($site->id, $manager->getCurrentSite()->id);
    }

    public function test_it_uses_first_site_when_no_session_data()
    {
        $site = Site::factory()->create();

        $manager = new FilamentSiteManager;

        $this->assertEquals($site->id, $manager->getCurrentSite()->id);
    }

    public function test_it_uses_first_site_when_session_has_invalid_site_id()
    {
        $site = Site::factory()->create();
        Session::put(FilamentSiteManager::MULTISITE_FILAMENT_ACTIVE_SITE_ID_SESSION, 999);

        $manager = new FilamentSiteManager;

        $this->assertEquals($site->id, $manager->getCurrentSite()->id);
    }
}
