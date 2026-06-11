<?php declare(strict_types=1);

use Bambamboole\LaravelTranslationDumper\DumpingTranslator;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Bambamboole\LaravelTranslationDumper\TranslationDumperInterface;

it('merges the package configuration under the translation namespace', function () {
    expect(config('translation.dump_translations'))->toBeTrue()
        ->and(config('translation.dump_prefix'))->toBe('x-')
        ->and(config('translation.ignore_keys'))->toContain('validation.custom');
});

it('decorates the translator so missing keys are recorded', function () {
    expect(app('translator'))->toBeInstanceOf(DumpingTranslator::class);
});

it('binds the configured translation dumper', function () {
    expect(app(TranslationDumperInterface::class))->toBeInstanceOf(TranslationDumper::class);
});

it('still resolves translations through the decorated translator', function () {
    // A missing key is returned verbatim by Laravel; the decorator records it
    // for dumping without changing the returned value.
    expect(trans('some.missing.key'))->toBe('some.missing.key')
        ->and(app('translator')->getLocale())->toBe(config('app.locale'));
});
