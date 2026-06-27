<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Tests\Traits;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;
use Zoker\FilamentMultisite\Traits\HasMultisite;

class HasMultisiteTest extends TestCase
{
    use RefreshDatabase;

    private Site $site1;

    private Site $site2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\Filament\Filament::class, function ($mock) {
            $mock->shouldReceive('isServing')->andReturn(false);
        });

        // Подменяем инстанс фасада
        Filament::swap(app(\Filament\Filament::class));

        Site::truncate();

        $this->site1 = Site::factory()->create([
            'code' => 'site1',
            'domain' => 'site1.example.com',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->site2 = Site::factory()->create([
            'code' => 'site2',
            'domain' => 'site2.example.com',
            'locale' => 'ru',
            'is_active' => true,
        ]);
    }

    public function test_global_scope_filters_by_current_site(): void
    {
        SiteManager::setCurrentSite($this->site1);

        // Create test models
        TestModel::factory()->create(['site_id' => $this->site1->id, 'name' => 'Model 1']);
        TestModel::factory()->create(['site_id' => $this->site2->id, 'name' => 'Model 2']);

        // Global scope should only return models from current site
        $models = TestModel::all();
        $this->assertCount(1, $models);
        $this->assertEquals('Model 1', $models->first()->name);
    }

    public function test_for_site_scope_without_global_scope(): void
    {
        // Set current site to site1
        SiteManager::setCurrentSite($this->site1);

        // Create test models
        TestModel::factory()->create(['site_id' => $this->site1->id, 'name' => 'Model 1']);
        TestModel::factory()->create(['site_id' => $this->site2->id, 'name' => 'Model 2']);

        // forSite should return models from specific site ignoring global scope
        $site2Models = TestModel::forSite($this->site2)->get();
        $this->assertCount(1, $site2Models);
        $this->assertEquals('Model 2', $site2Models->first()->name);
    }

    public function test_all_sites_scope_removes_global_scope(): void
    {
        // Set current site to site1
        SiteManager::setCurrentSite($this->site1);

        // Create test models
        TestModel::factory()->create(['site_id' => $this->site1->id, 'name' => 'Model 1']);
        TestModel::factory()->create(['site_id' => $this->site2->id, 'name' => 'Model 2']);

        // allSites should return all models ignoring global scope
        $allModels = TestModel::allSites()->get();
        $this->assertCount(2, $allModels);
    }

    public function test_site_relationship(): void
    {
        $model = TestModel::factory()->create(['site_id' => $this->site1->id]);

        $this->assertInstanceOf(Site::class, $model->site);
        $this->assertEquals($this->site1->id, $model->site->id);
    }

    public function test_set_site_method(): void
    {
        $model = new TestModel;

        $model->setSite($this->site2);
        $this->assertEquals($this->site2->id, $model->site_id);

        $model->setSite($this->site1->id);
        $this->assertEquals($this->site1->id, $model->site_id);
    }

    public function test_create_for_current_site(): void
    {
        SiteManager::setCurrentSite($this->site1);

        $model = TestModel::createForCurrentSite(['name' => 'Test Model']);

        $this->assertEquals($this->site1->id, $model->site_id);
        $this->assertEquals('Test Model', $model->name);
    }

    public function test_create_for_specific_site(): void
    {
        $model = TestModel::createForSite($this->site2, ['name' => 'Test Model']);

        $this->assertEquals($this->site2->id, $model->site_id);
        $this->assertEquals('Test Model', $model->name);
    }

    public function test_for_sites_scope_returns_models_from_multiple_sites(): void
    {
        $site3 = Site::factory()->create(['code' => 'site3', 'is_active' => true]);

        SiteManager::setCurrentSite($this->site1);

        TestModel::factory()->create(['site_id' => $this->site1->id, 'name' => 'Model 1']);
        TestModel::factory()->create(['site_id' => $this->site2->id, 'name' => 'Model 2']);
        TestModel::factory()->create(['site_id' => $site3->id, 'name' => 'Model 3']);

        $models = TestModel::forSites([$this->site1->id, $this->site2->id])->get();

        $this->assertCount(2, $models);
        $this->assertEqualsCanonicalizing(['Model 1', 'Model 2'], $models->pluck('name')->all());
    }

    public function test_except_site_scope_excludes_a_site(): void
    {
        SiteManager::setCurrentSite($this->site1);

        TestModel::factory()->create(['site_id' => $this->site1->id, 'name' => 'Model 1']);
        TestModel::factory()->create(['site_id' => $this->site2->id, 'name' => 'Model 2']);

        $models = TestModel::exceptSite($this->site1)->get();

        $this->assertCount(1, $models);
        $this->assertEquals('Model 2', $models->first()->name);
    }
}

/**
 * Test model for testing HasMultisite trait.
 */
class TestModel extends Model
{
    use HasFactory;
    use HasMultisite;

    protected $table = 'test_models';

    protected $fillable = ['name', 'site_id'];

    protected static function newFactory(): Factory
    {
        return TestModelFactory::new();
    }
}

class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'site_id' => Site::factory(),
        ];
    }
}
