<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Feature;

use Bambamboole\LaravelTranslationDumper\ArrayExporter;
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
        $this->createSubject($workingPath)->dump(['test.dotted.additional', 'test undotted key', 'word', 'Works.']);

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
