<?php declare(strict_types=1);

use Bambamboole\LaravelTranslationDumper\ArrayExporter;
use Bambamboole\LaravelTranslationDumper\DumpingTranslator;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Bambamboole\LaravelTranslationDumper\TranslationDumperInterface;
use Illuminate\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

it('merges the package configuration under the translation namespace', function () {
    expect(config('translation.dump_translations'))->toBeTrue()
        ->and(config('translation.dump_prefix'))->toBe('x-')
        ->and(config('translation.dump_namespaces'))->toBe([])
        ->and(config('translation.ignore_keys'))->toContain('validation.custom');
});

it('decorates the translator so missing keys are recorded', function () {
    expect(app('translator'))->toBeInstanceOf(DumpingTranslator::class);
});

it('binds the configured translation dumper', function () {
    expect(app(TranslationDumperInterface::class))->toBeInstanceOf(TranslationDumper::class);
});

it('still resolves translations through the decorated translator', function () {
    expect(trans('some.missing.key'))->toBe('some.missing.key')
        ->and(app('translator')->getLocale())->toBe(config('app.locale'));
});

it('ignores missing package translation keys unless their namespace is enabled', function () {
    $filesystem = $this->createMock(Filesystem::class);

    app()->instance(Filesystem::class, $filesystem);
    app()->afterResolving('translator', fn ($translator) => $translator->addNamespace('courier', '/packages/courier/lang'));

    $filesystem->expects($this->never())->method('put');

    $translator = app('translator');
    $translator->get('courier::messages.welcome');
    $translator->__destruct();
});

it('dumps enabled package translation namespaces into the registered package lang path', function () {
    config(['translation.dump_namespaces' => ['courier']]);

    $filesystem = $this->createMock(Filesystem::class);
    $expectedFile = '/packages/courier/lang/en/messages.php';
    $expectedContent = ArrayExporter::export([
        'welcome' => 'x-courier::messages.welcome',
    ]);

    app()->instance(Filesystem::class, $filesystem);
    app()->afterResolving('translator', fn ($translator) => $translator->addNamespace('courier', '/packages/courier/lang'));

    $filesystem->expects($this->once())
        ->method('exists')
        ->with($expectedFile)
        ->willReturn(false);

    $filesystem->expects($this->once())
        ->method('ensureDirectoryExists')
        ->with('/packages/courier/lang/en');

    $filesystem->expects($this->once())
        ->method('put')
        ->with($expectedFile, $expectedContent, true);

    $translator = app('translator');
    $translator->get('courier::messages.welcome');
    $translator->__destruct();
});

it('uses configured dump namespaces without requiring them to be registered first', function () {
    config(['translation.dump_namespaces' => ['courier']]);

    $logger = $this->createMock(LoggerInterface::class);

    app()->instance(LoggerInterface::class, $logger);

    $logger->expects($this->once())
        ->method('error')
        ->with(
            'Failed to dump missing translations.',
            $this->callback(
                fn (array $context) => ($context['exception'] ?? null) instanceof InvalidArgumentException
                    && str_contains($context['exception']->getMessage(), 'courier'),
            ),
        );

    $translator = app('translator');
    $translator->get('courier::messages.welcome');
    $translator->__destruct();
});
