<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Feature;

use Bambamboole\LaravelTranslationDumper\TranslationFinder;
use PHPUnit\Framework\TestCase;

class TranslationFinderTest extends TestCase
{
    public function testItCanFindTranslationsInCode(): void
    {
        $translations = $this->createSubject()->findTranslations();

        $expected = [
            'test.dotted',
            'test.dotted.additional',
            'This is just a sentence',
            'test undotted key',
            'test',
        ];
        foreach ($expected as $item) {
            self::assertContains($item, $translations);
        }
    }

    private function createSubject(): TranslationFinder
    {
        return new TranslationFinder(__DIR__.'/fixtures/code');
    }
}
