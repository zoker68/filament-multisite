<?php

namespace Zoker\FilamentMultisite\Tests\Unit\Providers;

use Illuminate\Support\ServiceProvider;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteServiceProvider;
use Zoker\FilamentMultisite\Tests\TestCase;

class FilamentMultisiteServiceProviderTest extends TestCase
{
    public function test_every_publishable_source_path_exists()
    {
        $paths = ServiceProvider::pathsToPublish(FilamentMultisiteServiceProvider::class);

        $this->assertNotEmpty($paths, 'The provider registered no publishable paths.');

        foreach (array_keys($paths) as $source) {
            // vendor:publish aborts with "Can't locate path" on a missing source.
            $this->assertFileExists($source);
        }
    }

    public function test_migrations_are_published_under_the_documented_tag()
    {
        $this->assertContains('multisite-migrations', ServiceProvider::publishableGroups());

        $paths = ServiceProvider::pathsToPublish(FilamentMultisiteServiceProvider::class, 'multisite-migrations');

        $this->assertCount(5, $paths);
    }
}
