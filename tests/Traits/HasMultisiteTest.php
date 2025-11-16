<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;

class HasMultisiteTest extends TestCase
{
    use RefreshDatabase;

    private Site $site1;

    private Site $site2;

    protected function setUp(): void
    {
        parent::setUp();

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
}

/**
 * Test model for testing HasMultisite trait.
 */
class TestModel extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Zoker\FilamentMultisite\Traits\HasMultisite;

    protected $table = 'test_models';

    protected $fillable = ['name', 'site_id'];

    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return TestModelFactory::new();
    }
}

class TestModelFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TestModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'site_id' => \Zoker\FilamentMultisite\Models\Site::factory(),
        ];
    }
}
