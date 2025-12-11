<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Traits;

use Zoker\FilamentMultisite\Models\Site;

trait HasMultisiteResource
{
    /**
     * @return array<string>
     */
    public static function getTranslatableLocales(): array
    {
        return Site::getUsingLocales();
    }

    /**
     * @return array<string>
     */
    public static function getTranslatableAttributes(): array
    {
        return [];
    }

    public static function getDefaultTranslatableLocale(): string
    {
        return static::getTranslatableLocales()[0];
    }
}
