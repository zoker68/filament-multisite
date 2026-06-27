<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Tests\Unit\Traits;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Tests\TestCase;
use Zoker\FilamentMultisite\Traits\HasMultisiteResource;

class HasMultisiteResourceTest extends TestCase
{
    public function test_translatable_locales_come_from_the_sites(): void
    {
        Site::truncate();
        $this->createActiveSite(['locale' => 'en']);
        $this->createActiveSite(['locale' => 'ru']);
        $this->createActiveSite(['locale' => 'en']);

        $locales = HasMultisiteResourceTestResource::getTranslatableLocales();

        sort($locales);
        $this->assertEquals(['en', 'ru'], $locales);
    }

    public function test_default_translatable_locale_is_the_first_one(): void
    {
        Site::truncate();
        $this->createActiveSite(['locale' => 'sl']);

        $this->assertEquals('sl', HasMultisiteResourceTestResource::getDefaultTranslatableLocale());
    }

    public function test_translatable_attributes_are_empty_by_default(): void
    {
        $this->assertEquals([], HasMultisiteResourceTestResource::getTranslatableAttributes());
    }
}

class HasMultisiteResourceTestResource
{
    use HasMultisiteResource;
}
