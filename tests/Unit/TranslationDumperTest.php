<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Unit;

use Bambamboole\LaravelTranslationDumper\ArrayExporter;
use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TranslationDumperTest extends TestCase
{
    private const TEST_LANGUAGE_FILE_PATH = '/foo/bar';

    private const TEST_LOCALE = 'de';

    private MockObject|Filesystem $filesystem;

    private MockObject|ArrayExporter $exporter;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->exporter = $this->createMock(ArrayExporter::class);
    }

    /** @dataProvider provideTestData */
    public function testItDumpsDottedKeysAsExpected(array $given, array $expected): void
    {
        if (empty($expected)) {
            $this->filesystem
                ->expects($this->never())
                ->method('exists');
            $this->exporter
                ->expects($this->never())
                ->method('export');
            $this->filesystem
                ->expects($this->never())
                ->method('put');
        } else {
            foreach ($expected as $key => $expectedArray) {
                $file = self::TEST_LANGUAGE_FILE_PATH.'/'.self::TEST_LOCALE.'/'.$key.'.php';
                $this->filesystem
                    ->expects($this->once())
                    ->method('exists')
                    ->with($file)
                    ->willReturn(false);
                $this->exporter
                    ->expects($this->once())
                    ->method('export')
                    ->with($expectedArray)
                    ->willReturn('test');
                $this->filesystem
                    ->expects($this->once())
                    ->method('put')
                    ->with($file, 'test');
            }
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
            $this->exporter,
            self::TEST_LANGUAGE_FILE_PATH,
            self::TEST_LOCALE,
        );
    }
}
