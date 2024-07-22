<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Illuminate\Filesystem\Filesystem;

class TranslationDumper implements TranslationDumperInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ArrayExporter $exporter,
        private readonly string $languageFilePath,
        private string $locale,
        private readonly string $dumpPrefix = 'x-',
    ) {}

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function dump(array $translationKeys): void
    {
        $dottedStrings = $this->filterForDottedTranslationKeys($translationKeys);
        $this->dumpDottedKeys($dottedStrings);
        $this->dumpNonDottedKeys(array_unique(array_diff($translationKeys, $dottedStrings)));
    }

    private function dumpDottedKeys(array $dottedStrings): void
    {
        $result = $this->transformDottedStringsToArray($dottedStrings);

        foreach ($result as $key => $value) {
            $path = $this->languageFilePath.'/'.$this->locale;
            $file = "{$path}/{$key}.php";
            $keys = $this->mergeWithExistingKeys($file, $value);

            $content = $this->exporter->export($keys);
            $this->filesystem->ensureDirectoryExists($path);
            $this->filesystem->put($file, $content);
        }
    }

    private function filterForDottedTranslationKeys(array $unfilteredKeys): array
    {
        $keys = array_filter(
            $unfilteredKeys,
            function (string $key) {
                if (str_contains($key, ' ')) {
                    return false;
                }
                if (! str_contains($key, '.')) {
                    return false;
                }
                if (str_ends_with($key, '.')) {
                    return false;
                }

                return true;
            });
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
            $current[$lastKey] = $this->dumpPrefix.$dottedString;
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

    private function dumpNonDottedKeys(array $nonDottedKeys): void
    {
        if (empty($nonDottedKeys)) {
            return;
        }
        $file = "{$this->languageFilePath}/{$this->locale}.json";
        if ($this->filesystem->exists($file)) {
            $existingKeys = json_decode($this->filesystem->get($file), true);
        } else {
            $existingKeys = [];
        }

        $nonDottedKeys = array_combine($nonDottedKeys, array_map(fn ($key) => $this->dumpPrefix.$key, $nonDottedKeys));
        $keys = array_merge($existingKeys, $nonDottedKeys);
        ksort($keys, SORT_NATURAL | SORT_FLAG_CASE);
        $this->filesystem->ensureDirectoryExists($this->languageFilePath);
        $this->filesystem->put($file, json_encode($keys, JSON_PRETTY_PRINT).PHP_EOL);
    }
}
