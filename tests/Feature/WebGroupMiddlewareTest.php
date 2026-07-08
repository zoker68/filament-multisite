<?php

namespace Zoker\FilamentMultisite\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Http\Middleware\MultisiteMiddleware;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;

class WebGroupMiddlewareTest extends TestCase
{
    public function test_it_registers_multisite_middleware_on_the_web_group()
    {
        $groups = app('router')->getMiddlewareGroups();

        $this->assertArrayHasKey('web', $groups);
        $this->assertContains(MultisiteMiddleware::class, $groups['web']);
    }

    /**
     * Regression: Livewire's `/livewire/update` endpoint lives on the `web` group but
     * outside the `Route::multisite()` macro. Without the middleware on the `web` group
     * the site was never resolved for these requests and silently fell back to the
     * default. Here a Livewire-style request must resolve the site from the Referer.
     */
    public function test_it_resolves_site_from_referer_on_web_livewire_requests()
    {
        Site::query()->delete();

        $this->createActiveSite(['prefix' => null, 'is_default' => true]);
        $this->createActiveSite(['prefix' => 'ru', 'locale' => 'ru']);

        // A plain `web`-group route — NOT registered through Route::multisite().
        Route::middleware('web')->get('/livewire/update', function () {
            return response()->json([
                'prefix' => SiteManager::getCurrentSite()->prefix,
            ]);
        });

        $this->withHeaders([
            'X-Livewire' => 'true',
            'Referer' => 'http://localhost/ru/some-page',
        ])->get('/livewire/update')
            ->assertStatus(200)
            ->assertJson(['prefix' => 'ru']);
    }
}
