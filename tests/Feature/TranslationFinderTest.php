<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Feature;

use Bambamboole\LaravelTranslationDumper\TranslationFinder;
use PHPUnit\Framework\TestCase;

class TranslationFinderTest extends TestCase
{
    public function testItCanFindTranslationsInCode(): void
    {
        $translations = $this->createSubject()->findTranslations();

        self::assertSame([
            'test.dotted',
            'test.dotted.additional',
            'This is just a sentence',
            'test undotted key',
            'test',
        ], $translations);
    }

    private function createSubject(): TranslationFinder
    {
        return new TranslationFinder(__DIR__.'/fixtures/code');
    }
}
