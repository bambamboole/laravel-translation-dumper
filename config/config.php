<?php declare(strict_types=1);

/*
 * You can place your custom package configuration in here.
 */
return [
    'dump_translations' => env('DUMP_TRANSLATIONS', false),
    'dumper' => \Bambamboole\LaravelTranslationDumper\TranslationDumper::class,
    'dump_prefix' => 'x-',
];
