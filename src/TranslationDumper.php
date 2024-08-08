<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class TranslationDumper implements TranslationDumperInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ArrayExporter $exporter,
        private readonly string $languageFilePath,
        private string $locale,
    ) {}

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /** @param Translation[] $translations */
    public function dump(array $translations): void
    {
        $jsonTranslations = [];
        $phpTranslations = [];
        foreach ($translations as $translation) {
            if (TranslationIdentifier::identify($translation->key) === TranslationType::PHP) {
                $phpTranslations[] = $translation;
            } else {
                $jsonTranslations[] = $translation;
            }
        }
        $this->dumpPhpTranslations($phpTranslations);
        $this->dumpJsonTranslations($jsonTranslations);
    }

    /** @param Translation[] $translations */
    private function dumpPhpTranslations(array $translations): void
    {
        $result = $this->transformDottedStringsToArray($translations);
        foreach ($result as $key => $value) {
            $path = $this->languageFilePath.'/'.$this->locale;
            $file = "{$path}/{$key}.php";
            $keys = $this->mergeWithExistingKeys($file, $value);

            $content = $this->exporter->export($keys);
            $this->filesystem->ensureDirectoryExists($path);
            $this->filesystem->put($file, $content);
        }
    }

    /** @param Translation[] $translations */
    private function dumpJsonTranslations(array $translations): void
    {
        if (empty($translations)) {
            return;
        }
        $newTranslations = collect($translations)
            ->mapWithKeys(function (Translation $translation) {
                return [$translation->key => $this->prepareTranslationValue($translation)];
            })
            ->toArray();
        $file = "{$this->languageFilePath}/{$this->locale}.json";
        $existingKeys = $this->filesystem->exists($file)
            ? json_decode($this->filesystem->get($file), true)
            : [];

        $mergedTranslations = array_merge($existingKeys, $newTranslations);
        ksort($mergedTranslations, SORT_NATURAL | SORT_FLAG_CASE);
        $this->filesystem->ensureDirectoryExists($this->languageFilePath);
        $this->filesystem->put($file, json_encode($mergedTranslations, JSON_PRETTY_PRINT).PHP_EOL);
    }

    /** @param Translation[] $translations */
    private function transformDottedStringsToArray(array $translations): array
    {
        $result = [];

        foreach ($translations as $translation) {
            $keys = explode('.', $translation->key);
            $current = &$result;

            while (count($keys) > 1) {
                $key = array_shift($keys);
                if (! isset($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }

            $lastKey = array_shift($keys);
            $current[$lastKey] = $this->prepareTranslationValue($translation);
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

    private function prepareTranslationValue(Translation $translation): string
    {
        $value = $translation->translation;
        foreach ($translation->replace as $key => $replace) {
            $value .= sprintf(' :%s %s', $key, $replace ?? 'n/a');
        }

        return $value;
    }
}
