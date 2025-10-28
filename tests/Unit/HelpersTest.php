<?php

namespace Zoker\FilamentMultisite\Tests\Unit;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {

        parent::setUp();

        Site::truncate();
        $this->site = $this->createActiveSite();
    }

    public function test_it_generates_route_without_locale_parameter()
    {
        // Set up test routes
        Route::multisite(function () {
            Route::get('/test-route', function () {
                return response()->json([
                    'multisite_route' => multisite_route('test.route'),
                ]);
            })->name('test.route');
        });

        $this->get($this->site->prefix . '/test-route')
            ->assertJson([
                'multisite_route' => config('app.url') . '/' . $this->site->prefix . '/test-route',
            ]);
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

        $this->get('/' . $this->site->prefix . '/path')->assertJson([
            'route_' . $this->site->locale => config('app.url') . '/' . $this->site->prefix . '/path',
            'route_' . $site2->locale => config('app.url') . '/' . $this->site->prefix . '/pot',
        ]);

        $this->get('/' . $site2->prefix . '/pot')->assertJson([
            'route_' . $this->site->locale => config('app.url') . '/' . $site2->prefix . '/path',
            'route_' . $site2->locale => config('app.url') . '/' . $site2->prefix . '/pot',
        ]);
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

        $this->get('/regular-route')
            ->assertJson([
                'route' => route('regular.route'),
            ]);
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
        Route::multisite(function () {
            Route::get('test-route', function () {
                return response()->json([
                    'absolute' => multisite_route('test.route'),
                    'relative' => multisite_route('test.route', [], false),
                ]);
            })->name('test.route');
        });

        $this->get('/' . $this->site->prefix . '/test-route')->assertJson([
            'absolute' => config('app.url') . '/' . $this->site->prefix . '/test-route',
            'relative' => '/' . $this->site->prefix . '/test-route',
        ]);
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

        $this->get('/test-route')->assertJson([
            'route' => config('app.url') . '/test-route',
        ]);
    }
}
