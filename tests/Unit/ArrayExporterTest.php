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

        self::assertEquals($out, ArrayExporter::export($in));
    }

    public function testItOmitsIndexKeys(): void
    {
        $in = [
            'foo' => 'bar',
            'baz' => [
                'buzz' => [
                    'light year',
                    'light month',
                ],
            ],
        ];
        $out = <<<'EOT'
<?php declare(strict_types=1);

return [
    'foo' => 'bar',
    'baz' => [
        'buzz' => [
            'light year',
            'light month',
        ],
    ],
];

EOT;

        self::assertEquals($out, ArrayExporter::export($in));
    }

    public function testItHandlesMixedArrays(): void
    {
        $in = [
            'foo' => 'bar',
            'indexed' => [
                'first',
                'second',
                'third',
            ],
            'mixed' => [
                'string_key' => 'value',
                42 => 'numeric key',
                'another' => [
                    'nested',
                    'values',
                    'key' => 'with value',
                ],
            ],
            'empty_array' => [],
            0 => 'zero',
            1 => 'one',
        ];

        $out = <<<'EOT'
<?php declare(strict_types=1);

return [
    'foo' => 'bar',
    'indexed' => [
        'first',
        'second',
        'third',
    ],
    'mixed' => [
        'string_key' => 'value',
        42 => 'numeric key',
        'another' => [
            0 => 'nested',
            1 => 'values',
            'key' => 'with value',
        ],
    ],
    'empty_array' => [],
    0 => 'zero',
    1 => 'one',
];

EOT;

        self::assertEquals($out, ArrayExporter::export($in));
    }

    public function testItHandlesNonSequentialNumericKeys(): void
    {
        $in = [
            'sparse' => [
                5 => 'five',
                10 => 'ten',
                15 => 'fifteen',
            ],
            'reindexed' => [
                'first',
                1 => 'second',
                3 => 'fourth',
                2 => 'third',
            ],
        ];

        $out = <<<'EOT'
<?php declare(strict_types=1);

return [
    'sparse' => [
        5 => 'five',
        10 => 'ten',
        15 => 'fifteen',
    ],
    'reindexed' => [
        0 => 'first',
        1 => 'second',
        3 => 'fourth',
        2 => 'third',
    ],
];

EOT;

        self::assertEquals($out, ArrayExporter::export($in));
    }
}
