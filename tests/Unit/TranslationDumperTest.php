<?php declare(strict_types=1);

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Bambamboole\LaravelTranslationDumper\TranslationWriter;

it('routes a dotted php key into a flat top level group when no nested file exists', function () {
    $writer = $this->createMock(TranslationWriter::class);
    $writer->method('hasGroup')->willReturn(false);
    $writer->expects($this->once())
        ->method('writeGroup')
        ->with('de', 'foo', ['bar' => ['baz' => 'x-foo.bar.baz']]);

    (new TranslationDumper($writer, 'de'))->dump([new Translation('foo.bar.baz', 'x-foo.bar.baz')]);
});

it('targets an existing nested group file when one matches', function () {
    $writer = $this->createMock(TranslationWriter::class);
    $writer->method('hasGroup')->willReturnCallback(fn ($locale, $group) => $group === 'entities/salesOrder');
    $writer->expects($this->once())
        ->method('writeGroup')
        ->with('de', 'entities/salesOrder', ['title' => 'x-entities.salesOrder.title']);

    (new TranslationDumper($writer, 'de'))->dump([
        new Translation('entities.salesOrder.title', 'x-entities.salesOrder.title'),
    ]);
});

it('writes a non dotted key to the json file', function () {
    $writer = $this->createMock(TranslationWriter::class);
    $writer->expects($this->never())->method('writeGroup');
    $writer->expects($this->once())
        ->method('writeJson')
        ->with('de', ['Welcome back' => 'x-Welcome back']);

    (new TranslationDumper($writer, 'de'))->dump([new Translation('Welcome back', 'x-Welcome back')]);
});

it('writes every key into an explicit group without probing existing files', function () {
    $writer = $this->createMock(TranslationWriter::class);
    $writer->expects($this->never())->method('hasGroup');
    $writer->expects($this->once())
        ->method('writeGroup')
        ->with('de', 'entities/salesOrder', [
            'subtitle' => 'x-subtitle',
            'status' => ['open' => 'x-status.open'],
        ]);

    (new TranslationDumper($writer, 'de'))->dump([
        new Translation('subtitle', 'x-subtitle'),
        new Translation('status.open', 'x-status.open'),
    ], 'entities/salesOrder');
});
