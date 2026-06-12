<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class TranslationDumper implements TranslationDumperInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
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
        // Group every translation by the file it belongs in. A dotted key goes
        // into a flat top level file by default, but if a matching nested file
        // already exists we write into that one instead.
        $byFile = [];
        foreach ($translations as $translation) {
            [$group, $remainingKey] = $this->resolveTargetFile($translation->key);
            $byFile[$group][] = [$remainingKey, $this->prepareTranslationValue($translation)];
        }

        foreach ($byFile as $group => $entries) {
            $values = [];
            foreach ($entries as [$remainingKey, $value]) {
                Arr::set($values, $remainingKey, $value);
            }

            $file = "{$this->languageFilePath}/{$this->locale}/{$group}.php";
            $keys = $this->mergeWithExistingKeys($file, $values);

            $this->filesystem->ensureDirectoryExists(dirname($file));
            $this->filesystem->put($file, ArrayExporter::export($keys), lock: true);
        }
    }

    /**
     * Decide which file a dotted key should be written to. By default the first
     * segment becomes a flat top level file (e.g. entities.salesOrder.title ->
     * entities.php). If a deeper nested file already exists on disk we target
     * that one instead (entities/salesOrder.php), so existing nested files keep
     * receiving their keys while new nested files are never created.
     *
     * @return array{0: string, 1: string} the group path and the remaining dotted key
     */
    private function resolveTargetFile(string $dottedKey): array
    {
        $segments = explode('.', $dottedKey);
        $base = "{$this->languageFilePath}/{$this->locale}";

        // Walk from the deepest possible nested file down to the top level and
        // use the first one that already exists, so the most specific existing
        // file wins and we stop probing as soon as we find it.
        $depth = 1;
        for ($candidateDepth = count($segments) - 1; $candidateDepth >= 2; $candidateDepth--) {
            $candidate = $base.'/'.implode('/', array_slice($segments, 0, $candidateDepth)).'.php';
            if ($this->filesystem->exists($candidate)) {
                $depth = $candidateDepth;
                break;
            }
        }

        return [
            implode('/', array_slice($segments, 0, $depth)),
            implode('.', array_slice($segments, $depth)),
        ];
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
        $this->filesystem->put(
            $file,
            json_encode($mergedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
            lock: true,
        );
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
