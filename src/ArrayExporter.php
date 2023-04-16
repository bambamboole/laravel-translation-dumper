<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

class ArrayExporter
{
    public function export(array $array): string
    {
        $export = var_export($array, true);
        $export = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(['/\s*array\s\($/', '/\)(,)?$/', '/\s=>\s$/'], [null, ']$1', ' => ['], $array);

        return "<?php declare(strict_types=1);\n\nreturn "
            .implode(PHP_EOL, array_filter(['['] + $array))
            .';'."\n";
    }
}
