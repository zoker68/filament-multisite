<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Tests\Feature;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Models\SiteGroup;
use Zoker\FilamentMultisite\Services\AlternateLinks;
use Zoker\FilamentMultisite\Tests\TestCase;

class AlternateLinksFallbackTest extends TestCase
{
    private Site $default;

    private Site $en;

    private Site $domain;

    private Site $otherGroup;

    private Site $inactive;

    protected function setUp(): void
    {
        parent::setUp();

        AlternateLinks::clear();
        Site::query()->delete();

        $group = SiteGroup::factory()->create();

        $this->default = $this->createActiveSite(['site_group_id' => $group->id, 'prefix' => null, 'locale' => 'sl', 'is_default' => true]);
        $this->en = $this->createActiveSite(['site_group_id' => $group->id, 'prefix' => 'en', 'locale' => 'en']);
        $this->domain = $this->createActiveSite(['site_group_id' => $group->id, 'prefix' => null, 'domain' => 'shop.hr', 'locale' => 'hr']);
        $this->otherGroup = $this->createActiveSite(['site_group_id' => SiteGroup::factory()->create()->id, 'prefix' => 'de', 'locale' => 'de', 'is_default' => true]);
        $this->inactive = $this->createSite(['site_group_id' => $group->id, 'prefix' => 'fr', 'locale' => 'fr', 'is_active' => false]);
    }

    public function test_multisite_route_gets_alternates_for_the_active_sites_of_the_group(): void
    {
        $this->registerPageRoutes();

        $html = $this->get('/en/page/about')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="alternate" hreflang="sl" href="http://localhost/page/about" />', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="en" href="http://localhost/en/page/about" />', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="hr" href="https://shop.hr/page/about" />', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="x-default" href="http://localhost/page/about" />', $html);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/en/page/about" />', $html);
        $this->assertStringNotContainsString('hreflang="de"', $html);
        $this->assertStringNotContainsString('hreflang="fr"', $html);
        $this->assertSame(4, substr_count($html, 'hreflang='));
    }

    public function test_route_parameters_are_carried_over_and_the_query_string_is_dropped(): void
    {
        $this->registerPageRoutes();

        $html = $this->get('/docs/guide/a/b/c?utm_source=x&page=2')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="sl" href="http://localhost/docs/guide/a/b/c"', $html);
        $this->assertStringContainsString('hreflang="en" href="http://localhost/en/docs/guide/a/b/c"', $html);
        $this->assertStringContainsString('hreflang="hr" href="https://shop.hr/docs/guide/a/b/c"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/docs/guide/a/b/c" />', $html);
        $this->assertStringNotContainsString('utm_source', $html);
        $this->assertStringNotContainsString('multisite_prefix', $html);
    }

    public function test_bound_models_are_carried_over_by_their_route_key(): void
    {
        Route::bind('item', fn (string $value) => new BoundItem($value));
        $this->registerPageRoutes();

        $html = $this->get('/en/item/about')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="sl" href="http://localhost/item/key-about"', $html);
        $this->assertStringContainsString('hreflang="en" href="http://localhost/en/item/key-about"', $html);
    }

    public function test_route_outside_the_macros_gets_no_alternates(): void
    {
        $this->registerPageRoutes();

        $html = $this->get('/plain')->assertOk()->getContent();

        $this->assertStringNotContainsString('hreflang', $html);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/plain" />', $html);
    }

    public function test_unmatched_url_gets_no_alternates(): void
    {
        $this->registerPageRoutes();

        $this->get('/en/nothing/here')->assertNotFound();

        AlternateLinks::clear();

        $this->assertNull(request()->route());
        $this->assertCount(0, AlternateLinks::get());
        $this->assertStringNotContainsString('hreflang', Blade::render('<x-multisite::alternateLinksHead />'));
    }

    public function test_error_page_inside_a_multisite_route_gets_no_alternates(): void
    {
        $this->registerPageRoutes();

        $this->get('/en/missing/about')->assertNotFound();

        $this->assertNotNull(request()->route());
        $this->assertCount(0, AlternateLinks::get());
    }

    public function test_prefixed_site_without_a_multisite_variant_is_skipped(): void
    {
        // Prefixes are read when routes are registered: no prefixed site yet → no multisite.* routes.
        $this->en->delete();
        $this->otherGroup->delete();
        $this->registerPageRoutes();
        $this->createActiveSite(['site_group_id' => $this->default->site_group_id, 'prefix' => 'it', 'locale' => 'it']);

        $this->assertFalse(Route::has('multisite.page'));

        $html = $this->get('/page/about')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="sl" href="http://localhost/page/about"', $html);
        $this->assertStringContainsString('hreflang="hr" href="https://shop.hr/page/about"', $html);
        $this->assertStringNotContainsString('hreflang="it"', $html);
    }

    public function test_prefixed_site_missing_from_the_registered_prefix_constraint_is_skipped(): void
    {
        $this->registerPageRoutes();
        $this->createActiveSite(['site_group_id' => $this->default->site_group_id, 'prefix' => 'it', 'locale' => 'it']);

        $html = $this->get('/page/about')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="en" href="http://localhost/en/page/about"', $html);
        $this->assertStringNotContainsString('hreflang="it"', $html);
    }

    public function test_explicit_set_wins_over_the_fallback(): void
    {
        $this->registerPageRoutes();

        $html = $this->get('/en/custom')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="sl" href="https://custom.test/sl"', $html);
        $this->assertStringContainsString('hreflang="en" href="https://custom.test/en"', $html);
        $this->assertStringNotContainsString('/en/custom"', $html);
        $this->assertStringNotContainsString('hreflang="hr"', $html);
    }

    public function test_explicit_empty_set_disables_the_fallback(): void
    {
        $this->registerPageRoutes();

        $html = $this->get('/en/none')->assertOk()->getContent();

        $this->assertStringNotContainsString('hreflang', $html);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/en/none" />', $html);
    }

    public function test_explicit_canonical_survives_the_fallback(): void
    {
        $this->registerPageRoutes();

        $html = $this->get('/en/canonical')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="https://canonical.test/x" />', $html);
        $this->assertStringContainsString('hreflang="sl" href="http://localhost/canonical"', $html);
    }

    public function test_translated_routes_get_the_localized_path_of_each_site(): void
    {
        Lang::addLines(['routes.about' => 'o-nas'], 'sl');
        Lang::addLines(['routes.about' => 'about-us'], 'en');
        Lang::addLines(['routes.about' => 'o-nama'], 'hr');
        // Every active site's locale registers a variant; an untranslated one would reuse the en path and replace it.
        Lang::addLines(['routes.about' => 'ueber-uns'], 'de');

        Route::middleware('web')->group(function () {
            Route::multisite(function () {
                Route::translated(function () {
                    Route::get(__('routes.about'), fn () => Blade::render('<x-multisite::alternateLinksHead />'))->name('about');
                });
            });
        });

        $html = $this->get('/en/about-us')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="sl" href="http://localhost/o-nas"', $html);
        $this->assertStringContainsString('hreflang="en" href="http://localhost/en/about-us"', $html);
        $this->assertStringContainsString('hreflang="hr" href="https://shop.hr/o-nama"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/en/about-us" />', $html);
    }

    public function test_state_does_not_leak_between_requests(): void
    {
        $this->registerPageRoutes();

        $this->assertStringContainsString('hreflang', $this->get('/en/page/about')->getContent());

        $plain = $this->get('/plain')->getContent();
        $this->assertStringNotContainsString('hreflang', $plain);

        $this->get('/en/custom');
        $next = $this->get('/page/contact')->getContent();

        $this->assertStringContainsString('hreflang="en" href="http://localhost/en/page/contact"', $next);
        $this->assertStringNotContainsString('custom.test', $next);
        $this->assertStringNotContainsString('about', $next);
    }

    private function registerPageRoutes(): void
    {
        $head = fn () => Blade::render('<x-multisite::alternateLinksHead />');

        Route::middleware('web')->group(function () use ($head) {
            Route::multisite(function () use ($head) {
                Route::get('page/{slug}', $head)->name('page');
                Route::get('item/{item}', $head)->name('item');
                Route::get('docs/{slug}/{path}', $head)->where('path', '.+')->name('docs');
                Route::get('missing/{slug}', fn () => abort(404))->name('missing');

                Route::get('custom', function () use ($head) {
                    AlternateLinks::set([
                        ['site' => $this->default, 'url' => 'https://custom.test/sl'],
                        ['site' => $this->en, 'url' => 'https://custom.test/en'],
                    ]);

                    return $head();
                })->name('custom');

                Route::get('none', function () use ($head) {
                    AlternateLinks::set([]);

                    return $head();
                })->name('none');

                Route::get('canonical', function () use ($head) {
                    AlternateLinks::setCanonicalUrl('https://canonical.test/x');

                    return $head();
                })->name('canonical');
            });

            Route::get('plain', $head)->name('plain');
        });
    }
}

class BoundItem implements UrlRoutable
{
    public function __construct(private readonly string $slug) {}

    public function getRouteKey(): string
    {
        return 'key-' . $this->slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return null;
    }

    public function resolveChildRouteBinding($childType, $value, $field): ?self
    {
        return null;
    }
}
