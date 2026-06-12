<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Unit;

use Bambamboole\LaravelTranslationDumper\ArrayExporter;
use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TranslationDumperTest extends TestCase
{
    private const TEST_LANGUAGE_FILE_PATH = '/foo/bar';

    private const TEST_LOCALE = 'de';

    private MockObject|Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
    }

    #[DataProvider('provideTestData')]
    public function test_it_dumps_dotted_keys_as_expected(array $given, array $expected): void
    {
        // No translation files exist yet, so every key falls back to a flat
        // top level file named after its first segment.
        $this->filesystem->method('exists')->willReturn(false);

        if (empty($expected)) {
            $this->filesystem
                ->expects($this->never())
                ->method('put');
        } else {
            $key = array_key_first($expected);
            $file = self::TEST_LANGUAGE_FILE_PATH.'/'.self::TEST_LOCALE.'/'.$key.'.php';
            $this->filesystem
                ->expects($this->once())
                ->method('put')
                ->with($file, ArrayExporter::export($expected[$key]));
        }

        $this->createTranslationDumper()->dump($given);
    }

    public static function provideTestData(): array
    {
        return [
            [
                [new Translation('foo.bar.baz', 'x-foo.bar.baz')],
                ['foo' => ['bar' => ['baz' => 'x-foo.bar.baz']]],
            ],
        ];
    }

    private function createTranslationDumper(): TranslationDumper
    {
        return new TranslationDumper(
            $this->filesystem,
            self::TEST_LANGUAGE_FILE_PATH,
            self::TEST_LOCALE,
        );
    }
}
