<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Unit;

use Bambamboole\LaravelTranslationDumper\ArrayExporter;
use PHPUnit\Framework\TestCase;

class ArrayExporterTest extends TestCase
{
    public function test_it_can_dump_arrays_in_short_syntax(): void
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

    public function test_it_omits_index_keys(): void
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

    public function test_it_handles_mixed_arrays(): void
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

    public function test_it_handles_non_sequential_numeric_keys(): void
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
