<?php

namespace Zoker\FilamentMultisite\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Models\SiteGroup;

class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'site_group_id' => null,
            'code' => $this->faker->unique()->slug,
            'name' => $this->faker->name,
            'label' => $this->faker->name,
            'prefix' => $this->faker->slug,
            'locale' => $this->faker->locale,
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function default(): self
    {
        return $this->state(['is_default' => true]);
    }

    public function forGroup(int|SiteGroup $group): self
    {
        return $this->state(['site_group_id' => $group instanceof SiteGroup ? $group->id : $group]);
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }

    public function withPrefix(string $prefix): self
    {
        return $this->state(['prefix' => $prefix]);
    }

    public function withDomain(string $domain): self
    {
        return $this->state(['domain' => $domain]);
    }

    public function withLocale(string $locale): self
    {
        return $this->state(['locale' => $locale]);
    }
}
