<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Feature;

use Bambamboole\LaravelTranslationDumper\ArrayExporter;
use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class TranslationDumperTest extends TestCase
{
    private const TEST_LANGUAGE_PATH = __DIR__.'/fixtures/lang';

    public function testItDumpsKeysAsExpected(): void
    {
        $fs = new Filesystem();
        $testFolderName = uniqid();
        $workingPath = str_replace('lang', $testFolderName, self::TEST_LANGUAGE_PATH);

        $fs->copyDirectory(self::TEST_LANGUAGE_PATH, $workingPath);
        $translations = [
            new Translation('test.dotted.additional', 'x-test.dotted.additional'),
            new Translation('test.dotted.replacement', 'x-test.dotted.replacement', ['name' => 'foo']),
            new Translation('test undotted key', 'x-test undotted key'),
            new Translation('word', 'x-word'),
            new Translation('Works.', 'x-Works.'),
        ];
        $this->createSubject($workingPath)->dump($translations);

        $files = $fs->allFiles($workingPath);

        foreach ($files as $file) {
            $this->assertFileEquals(
                str_replace($testFolderName, 'expected', $file->getPathname()),
                $file->getPathname(),
            );
        }

        $fs->deleteDirectory($workingPath);
    }

    private function createSubject(string $folder): TranslationDumper
    {
        return new TranslationDumper(
            new Filesystem(),
            new ArrayExporter(),
            $folder,
            'en',
        );
    }
}
