<?php

namespace Zoker\FilamentMultisite\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Zoker\FilamentMultisite\Models\SiteGroup;

class SiteGroupFactory extends Factory
{
    protected $model = SiteGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'code' => $this->faker->unique()->slug(2),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
