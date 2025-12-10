<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\Traits;

trait HasMultisiteResource
{
    public static function getTranslatableLocales(): array
    {
        return filament('spatie-translatable')->getDefaultLocales();
    }

    public static function getTranslatableAttributes(): array
    {
        return [];
    }
}
