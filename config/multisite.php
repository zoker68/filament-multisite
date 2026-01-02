<?php

return [
    /**
     * ID of the site to use as canonical
     * If null, canonical link will not be generated
     */
    'canonical_site_id' => env('MULTISITE_CANONICAL_SITE_ID', null),

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
