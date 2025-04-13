<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Brick\VarExporter\VarExporter;

class ArrayExporter
{
    public static function export(array $array): string
    {
        return implode("\n", [
            "<?php declare(strict_types=1);\n",
            VarExporter::export($array, VarExporter::ADD_RETURN | VarExporter::TRAILING_COMMA_IN_ARRAY),
        ]);
    }
}
