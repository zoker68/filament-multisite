<?php

namespace Zoker\FilamentMultisite\Tests\Unit\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Zoker\FilamentMultisite\Events\SiteChanged;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Services\SiteManager;
use Zoker\FilamentMultisite\Tests\TestCase;

class SiteManagerTest extends TestCase
{
    private SiteManager $siteManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteManager = new SiteManager;
    }

    public function test_it_can_set_and_get_current_site()
    {
        $site = Site::factory()->create(['is_active' => true]);

        $this->siteManager->setCurrentSite($site);

        $this->assertTrue($site->is($this->siteManager->getCurrentSite()));
    }

    public function test_it_throws_exception_when_setting_inactive_site()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Site not found');

        $site = Site::factory()->create(['is_active' => false]);
        $this->siteManager->setCurrentSite($site);
    }

    public function test_it_throws_exception_when_setting_null_site()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Site not found');

        $this->siteManager->setCurrentSite(null);
    }

    public function test_it_sets_application_locale_when_setting_site()
    {
        $site = Site::factory()->create(['is_active' => true]);

        $this->siteManager->setCurrentSite($site);

        $this->assertEquals($site->locale, App::getLocale());
        $this->assertEquals($site->locale, Config::get('app.locale'));
    }

    public function test_it_sets_url_defaults_when_site_has_prefix()
    {
        $site = Site::factory()->create(['is_active' => true]);

        $this->siteManager->setCurrentSite($site);

        $this->assertEquals($site->prefix, URL::getDefaultParameters()['multisite_prefix']);
    }

    public function test_it_sets_current_site_by_id()
    {
        $site = Site::factory()->create(['is_active' => true]);

        $this->siteManager->setCurrentSiteById($site->id);

        $this->assertTrue($site->is($this->siteManager->getCurrentSite()));
    }

    public function test_it_throws_exception_when_setting_nonexistent_site_by_id()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Site not found');

        $this->siteManager->setCurrentSiteById(999);
    }

    public function test_it_sets_current_site_by_request_with_prefix()
    {
        $site = Site::factory()->create([
            'is_active' => true,
            'domain' => 'example.com',
            'prefix' => 'shop',
        ]);

        $request = Request::create('http://example.com/shop/some-path');

        $this->siteManager->setCurrentSiteByRequest($request);

        $this->assertTrue($site->is($this->siteManager->getCurrentSite()));
    }

    public function test_it_sets_current_site_by_request_without_prefix()
    {
        $site = Site::factory()->create([
            'is_active' => true,
            'domain' => 'example.com',
            'prefix' => null,
        ]);

        $request = Request::create('http://example.com/any-path');

        $this->siteManager->setCurrentSiteByRequest($request);

        $this->assertTrue($site->is($this->siteManager->getCurrentSite()));
    }

    public function test_it_handles_unknown_domain_by_falling_back_to_default()
    {
        Site::truncate();
        $defaultSite = Site::factory()->create([
            'is_active' => true,
            'domain' => null,
            'prefix' => null,
        ]);

        $request = Request::create(config('app.url') . '/any-path');

        $this->siteManager->setCurrentSiteByRequest($request);

        $this->assertTrue($defaultSite->is($this->siteManager->getCurrentSite()));
    }

    public function test_it_dispatches_site_changed_event()
    {
        Event::fake();

        $site1 = Site::factory()->create(['is_active' => true]);
        $site2 = Site::factory()->create(['is_active' => true]);

        $this->siteManager->setCurrentSite($site1);
        $this->siteManager->setCurrentSite($site2);

        Event::assertDispatched(SiteChanged::class, function (SiteChanged $event) use ($site1, $site2) {
            return $event->site->is($site2) &&
                   $event->previousSite->is($site1);
        });
    }

    public function test_it_does_not_dispatch_event_when_setting_same_site()
    {
        Event::fake();

        $site = Site::factory()->create(['is_active' => true]);

        $this->siteManager->setCurrentSite($site);
        $this->siteManager->setCurrentSite($site); // Same site

        // Should only be dispatched once
        Event::assertDispatchedTimes(SiteChanged::class, 1);
    }

    public function test_it_returns_exception_when_no_current_site_and_no_matching_default()
    {
        Site::truncate();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Site not found');
        $this->siteManager->getCurrentSite();
    }

    public function test_it_returns_default_site_when_no_current_site_set()
    {
        Site::truncate();

        $defaultSite = Site::factory()->create([
            'is_active' => true,
            'domain' => null,
            'prefix' => null,
        ]);

        $this->assertTrue($defaultSite->is($this->siteManager->getCurrentSite()));
    }

    public function test_it_returns_default_site_with_config_domain_when_no_current_site_set()
    {
        Site::truncate();

        config()->set('app.url', 'http://example.com');

        $defaultSite = Site::factory()->create([
            'is_active' => true,
            'domain' => 'example.com',
            'prefix' => null,
        ]);

        $this->assertTrue($defaultSite->is($this->siteManager->getCurrentSite()));
    }

    public function test_it_returns_site_when_only_sites_with_prefix_exist()
    {
        Site::truncate();

        $site = Site::factory()->create([
            'is_active' => true,
            'domain' => null,
            'prefix' => 'shop',
        ]);

        $this->assertTrue($site->is($this->siteManager->getCurrentSite()));
    }

    public function test_it_returns_exception_when_only_inactive_sites_exist()
    {
        Site::truncate();

        Site::factory()->create([
            'is_active' => false,
            'domain' => null,
            'prefix' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Site not found');

        $this->siteManager->getCurrentSite();
    }

    public function test_it_gets_domain_correctly()
    {
        config()->set('app.url', 'http://default.com');
        $this->assertEquals('example.com', $this->invokeMethod($this->siteManager, 'getDomain', ['example.com']));

        $this->assertNull($this->invokeMethod($this->siteManager, 'getDomain', ['default.com']));
    }

    /**
     * Helper method to test private/protected methods
     */
    private function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
