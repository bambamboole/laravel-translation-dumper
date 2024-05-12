<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Unit;

use Bambamboole\LaravelTranslationDumper\ArrayExporter;
use PHPUnit\Framework\TestCase;

class ArrayExporterTest extends TestCase
{
    public function testItCanDumpArraysInShortSyntax(): void
    {
        $in = [
            'foo' => 'bar',
            'baz' => [
                'buzz' => 'light year',
            ],
        ];
        $out = <<<'EOT'
<?php declare(strict_types=1);

return [
    'foo' => 'bar',
    'baz' => [
        'buzz' => 'light year',
    ],
];

EOT;

        self::assertEquals($out, (new ArrayExporter())->export($in));
    }
}
