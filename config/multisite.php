<?php

return [
    /**
     * Fallback locale for Filament interface
     */
    'fallback_locale' => env('MULTISITE_FALLBACK_LOCALE', 'en'),

    /**
     * Available locales for Filament interface
     */
    'filament_locales' => [
        'en' => 'English',
        // Uncomment to add more languages

        /*
        'ru' => 'Русский',
        'sl' => 'Slovenščina',
        'de' => 'Deutsch',
        'es' => 'Español',
        */
    ],
];
