<?php declare(strict_types=1);

return [
    'dump_translations' => env('DUMP_TRANSLATIONS', false),
    'dumper' => \Bambamboole\LaravelTranslationDumper\TranslationDumper::class,
    'dump_prefix' => 'x-',
    'dump_non_dotted_keys' => false,
    'ignore_keys' => [
        // These keys are used by Laravel validator to check if a custom validation message is present
        'validation.custom',
        'validation.values',
        // Ignore namespaced keys since it is not implemented yet
        '::',
    ],
];
