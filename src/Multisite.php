<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite;

use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Panel;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Zoker\FilamentMultisite\Models\Site;

final class Multisite implements Plugin
{
    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'filament-multisite';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(in: __DIR__ . '/../src/Filament/Resources', for: 'Zoker\\FilamentMultisite\\Filament\\Resources');

        $panel->plugin(SpatieTranslatablePlugin::make()->defaultLocales(Site::getUsingLocales())->persist());
    }

    public function boot(Panel $panel): void
    {
        $locales = config('multisite.locales', []);

        if (count($locales) > 1) {
            $panel->userMenuItems([
                Action::make('language_picker')
                    ->label('Change Language')
                    ->schema([
                        Select::make('language')
                            ->options(fn() => array_map(fn ($config) => $config['name'], $locales))
                            ->default(fn() => app()->getLocale())
                            ->required()
                    ])
                    ->action(function (array $data) {
                        $availableLocales = array_keys(config('multisite.locales', []));

                        if (! in_array($data['language'], $availableLocales)) {
                            return;
                        }

                        cookie()->queue('filament_locale', $data['language'], 60 * 60 * 24 * 365 * 10);

                        redirect(url()->previous());
                    })
            ]);
        }
    }
}
