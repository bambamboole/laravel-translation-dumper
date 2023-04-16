<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Illuminate\Filesystem\Filesystem;

class TranslationDumper implements TranslationDumperInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $languageFilePath,
        private string $locale,
    ) {
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function dump(array $translationKeys): void
    {
        $dottedStrings = $this->filterTranslationKeys($translationKeys);
        $result = $this->transformDottedStringsToArray($dottedStrings);

        foreach ($result as $key => $value) {
            $file = $this->languageFilePath."/{$this->locale}/{$key}.php";
            $keys = $this->mergeWithExistingKeys($file, $value);

            $content = $this->export($keys);
            $this->filesystem->put($file, $content);
        }
    }

    private function filterTranslationKeys(array $unfilteredKeys): array
    {
        $keys = array_filter($unfilteredKeys, fn (string $key) => ! str_contains($key, ' ') && str_contains($key, '.'));
        $keys = array_unique($keys);

        return array_values($keys);
    }

    private function transformDottedStringsToArray(array $dottedStrings): array
    {
        $result = [];

        foreach ($dottedStrings as $dottedString) {
            $keys = explode('.', $dottedString);
            $current = &$result;

            while (count($keys) > 1) {
                $key = array_shift($keys);
                if (! isset($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }

            $lastKey = array_shift($keys);
            $current[$lastKey] = 'x-'.$dottedString;
        }

        return $result;
    }

    private function mergeWithExistingKeys(string $filePath, array $newKeys): array
    {
        if ($this->filesystem->exists($filePath)) {
            $existingKeys = require $filePath;
        } else {
            $existingKeys = [];
        }

        return $this->mergeArrays($existingKeys, $newKeys);
    }

    private function mergeArrays(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeArrays($merged[$key], $value);
            } elseif (is_numeric($key)) {
                if (! in_array($value, $merged)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }
        ksort($merged);

        return $merged;
    }

    private function export(array $expression): string
    {
        $export = var_export($expression, true);
        $export = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(['/\s*array\s\($/', '/\)(,)?$/', '/\s=>\s$/'], [null, ']$1', ' => ['], $array);

        return "<?php declare(strict_types=1);\n\nreturn "
            .implode(PHP_EOL, array_filter(['['] + $array))
            .';'."\n";
    }
}
