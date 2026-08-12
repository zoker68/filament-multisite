<?php

namespace Zoker\FilamentMultisite\Tests\Unit;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {

        parent::setUp();

        Site::truncate();
        $this->site = $this->createActiveSite();
        $this->registerTestMultisiteRoutes();
    }

    public function test_it_generates_route_without_locale_parameter()
    {
        SiteManager::setCurrentSite($this->site);
        $route = multisite_route('test.route');
        $this->assertEquals(config('app.url') . '/' . $this->site->prefix . '/test', $route);
    }

    public function test_it_generates_route_with_specific_locale_with_multisite_prefix()
    {
        $site2 = $this->createActiveSite();

        Lang::addLines(['tests.route' => 'path'], $this->site->locale);
        Lang::addLines(['tests.route' => 'pot'], $site2->locale);

        Route::multisite(function () use ($site2) {
            Route::translated(function () use ($site2) {
                Route::get(__('tests.route'), function () use ($site2) {
                    return response()->json([
                        'route_' . $this->site->locale => multisite_route('test.route', locale: $this->site->locale),
                        'route_' . $site2->locale => multisite_route('test.route', locale: $site2->locale),
                    ]);
                })->name('test.route');
            });
        });

        SiteManager::setCurrentSite($this->site);
        $route = multisite_route('test.route', locale: $this->site->locale);
        $this->assertEquals(config('app.url') . '/' . $this->site->prefix . '/path', $route);

        SiteManager::setCurrentSite($site2);
        $route = multisite_route('test.route', locale: $site2->locale);
        $this->assertEquals(config('app.url') . '/' . $site2->prefix . '/pot', $route);
    }

    public function test_it_generates_route_with_specific_locale_without_multisite_prefix()
    {
        $site2 = $this->createActiveSite(
            ['prefix' => null]
        );

        Lang::addLines(['tests.route' => 'pot'], $site2->locale);

        Route::translated(function () use ($site2) {
            Route::get(__('tests.route'), function () use ($site2) {
                return response()->json([
                    'route_' . $site2->locale => multisite_route('test.route', locale: $site2->locale),
                ]);
            })->name('test.route');
        });

        $this->get('/pot')->assertJson([
            'route_' . $site2->locale => config('app.url') . '/pot',
        ]);
    }

    public function test_it_falls_back_to_regular_route_when_multisite_route_not_found()
    {
        // Create a route that's not in the multisite group
        Route::get('regular-route', function () {
            return response()->json([
                'route' => multisite_route('regular.route'),
            ]);
        })->name('regular.route');

        SiteManager::setCurrentSite($this->site);

        $this->assertEquals(route('regular.route'), multisite_route('regular.route'));

    }

    public function test_it_handles_route_parameters()
    {
        Route::multisite(function () {
            Route::get('user/{id}', function ($id) {
                return $id;
            })->name('user.profile');
        });

        $testId = mt_rand(1, 1000);
        $this->get('/' . $this->site->prefix . '/user/' . $testId)
            ->assertSeeText($testId);
    }

    public function test_it_handles_absolute_and_relative_urls()
    {
        SiteManager::setCurrentSite($this->site);

        $this->assertEquals(config('app.url') . '/' . $this->site->prefix . '/test', multisite_route('test.route'));
        $this->assertEquals('/' . $this->site->prefix . '/test', multisite_route('test.route', [], false));
    }

    public function test_it_handles_sites_without_prefix()
    {
        $siteWithoutPrefix = $this->createActiveSite([
            'prefix' => null,
        ]);

        Route::multisite(function () {
            Route::get('test-route', function () {
                return response()->json([
                    'route' => multisite_route('test.route'),
                ]);
            })->name('test.route');
        });

        SiteManager::setCurrentSite($siteWithoutPrefix);

        $this->assertEquals(config('app.url') . '/test', multisite_route('test.route'));
    }

    public function test_it_builds_absolute_urls_on_the_site_own_domain()
    {
        $siteWithDomain = $this->createActiveSite(['prefix' => null, 'domain' => 'other.example']);

        Route::get('branded', fn () => '')->name('branded.route');

        // Absolute URL lands on the site's OWN domain, not the current request host.
        $this->assertEquals('https://other.example/branded', multisite_route('branded.route', [], true, $siteWithDomain));
        // Relative path is unchanged.
        $this->assertEquals('/branded', multisite_route('branded.route', [], false, $siteWithDomain));
    }

    public function test_it_resolves_names_taken_from_a_prefixed_request_for_other_sites()
    {
        $otherPrefixed = $this->createActiveSite(['prefix' => 'zz', 'locale' => 'zz']);
        $noPrefix = $this->createActiveSite(['prefix' => null]);

        // Being on a prefixed site sets URL::defaults(multisite_prefix) to ITS prefix —
        // exactly the state where alternate links used to leak the current prefix.
        SiteManager::setCurrentSite($this->site);

        // request()->route()->getName() on a prefixed site returns "multisite.test.route".
        $this->assertEquals(
            config('app.url') . '/zz/test',
            multisite_route('multisite.test.route', [], true, $otherPrefixed)
        );

        $this->assertEquals(
            config('app.url') . '/test',
            multisite_route('multisite.test.route', [], true, $noPrefix)
        );

        $this->assertEquals(
            config('app.url') . '/' . $this->site->prefix . '/test',
            multisite_route('multisite.test.route', [], true, $this->site)
        );
    }

    public function test_it_resolves_names_taken_from_a_translated_request_for_other_sites()
    {
        $site2 = $this->createActiveSite(['prefix' => 'zz', 'locale' => 'zz']);

        Lang::addLines(['tests.route' => 'path'], $this->site->locale);
        Lang::addLines(['tests.route' => 'pot'], 'zz');

        Route::multisite(function () {
            Route::translated(function () {
                Route::get(__('tests.route'), fn () => '')->name('trans.route');
            });
        });

        SiteManager::setCurrentSite($this->site);

        // Current route name on a prefixed translated route: "multisite.{locale}.trans.route".
        $this->assertEquals(
            config('app.url') . '/zz/pot',
            multisite_route('multisite.' . $this->site->locale . '.trans.route', [], true, $site2)
        );
    }

    public function test_it_handles_current_site_helper()
    {
        SiteManager::setCurrentSite($this->site);
        $this->assertEquals($this->site, currentSite());
    }
}
