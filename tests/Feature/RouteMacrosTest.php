<?php

namespace Zoker\FilamentMultisite\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;

class RouteMacrosTest extends TestCase
{
    public function test_it_registers_multisite_routes()
    {
        $site = $this->createActiveSite();
        $siteSl = $this->createActiveSite([
            'locale' => 'sl',
            'prefix' => 'sl',
        ]);

        $this->registerTestMultisiteRoutes();

        $this->get('/' . $site->prefix . '/test')
            ->assertStatus(200)
            ->assertJson([
                'locale' => $site->locale,
                'prefix' => $site->prefix,
            ]);

        $this->get('/sl/test')
            ->assertStatus(200)
            ->assertJson([
                'locale' => $siteSl->locale,
                'prefix' => $siteSl->prefix,
            ]);
    }

    public function test_it_handles_unprefixed_routes()
    {
        Site::truncate(); // Remove default site

        $site = $this->createActiveSite([
            'prefix' => null,
        ]);

        $this->registerTestMultisiteRoutes();

        $this->get('/test')
            ->assertStatus(200)
            ->assertJson([
                'locale' => $site->locale,
                'prefix' => null,
            ]);
    }

    public function test_it_returns_404_for_unregistered_prefix()
    {
        $this->get('/nonexistent/test')->assertStatus(404);
    }

    public function test_it_registers_translated_routes()
    {
        $site1 = $this->createActiveSite([
            'prefix' => 'en',
            'locale' => 'en',
        ]);

        $site2 = $this->createActiveSite([
            'prefix' => 'sl',
            'locale' => 'sl',
        ]);

        Lang::addLines(['tests.route' => 'path'], 'en');
        Lang::addLines(['tests.route' => 'pot'], 'sl');

        Route::multisite(function () {
            Route::translated(function () {
                Route::get(__('tests.route'), function () {
                    return response()->json([
                        'locale' => app()->getLocale(),
                    ]);
                })->name('translated.route');
            });
        });

        // Test English route
        $responseEn = $this->get('/en/path');
        $responseEn->assertStatus(200);
        $responseEn->assertJson(['locale' => 'en']);

        // Test Slovenian route
        $responseFr = $this->get('/sl/pot');
        $responseFr->assertStatus(200);
        $responseFr->assertJson(['locale' => 'sl']);
    }
}
