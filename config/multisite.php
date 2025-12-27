<?php

return [
    /**
     * ID of the site to use as canonical
     * If null, canonical link will not be generated
     */
    'canonical_site_id' => null,

    /**
     * Available locales for Filament interface
     */
    'locales' => [
        'en' => [
            'name' => 'English',
            'icon' => 'heroicon-o-language',
        ],
        // Uncomment to add more languages
/*
        'ru' => [
            'name' => 'Русский',
            'icon' => 'heroicon-o-language',
        ],
        'sl' => [
            'name' => 'Slovenščina',
            'icon' => 'heroicon-o-language',
        ],
        'de' => [
            'name' => 'Deutsch',
            'icon' => 'heroicon-o-language',
        ],
        'es' => [
            'name' => 'Español',
            'icon' => 'heroicon-o-language',
        ],
*/
    ],
];
