<?php declare(strict_types=1);

return [
    'dump_translations' => env('DUMP_TRANSLATIONS', false),
    'dumper' => \Bambamboole\LaravelTranslationDumper\TranslationDumper::class,
    'dump_prefix' => 'x-',
    'ignore_keys' => [
        // This key is used by Laravel validator to check if a custom validation message is present
        'validation.custom',
        // Ignore namespaced keys since it is not implemented yet
        '::',
    ],
];
