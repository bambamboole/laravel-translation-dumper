<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Illuminate\Filesystem\Filesystem;

class FileTranslationWriter implements TranslationWriter
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $languageFilePath,
    ) {}

    public function hasGroup(string $locale, string $group): bool
    {
        return $this->filesystem->exists($this->groupPath($locale, $group));
    }

    public function writeGroup(string $locale, string $group, array $translations): void
    {
        if ($translations === []) {
            return;
        }

        $file = $this->groupPath($locale, $group);
        $existing = $this->filesystem->exists($file) ? require $file : [];
        $merged = $this->mergeArrays($existing, $translations);

        $this->filesystem->ensureDirectoryExists(dirname($file));
        $this->filesystem->put($file, ArrayExporter::export($merged), lock: true);
    }

    public function writeJson(string $locale, array $translations): void
    {
        if ($translations === []) {
            return;
        }

        $file = "{$this->languageFilePath}/{$locale}.json";
        $existing = $this->filesystem->exists($file)
            ? (array) json_decode($this->filesystem->get($file), true)
            : [];

        $merged = array_merge($existing, $translations);
        ksort($merged, SORT_NATURAL | SORT_FLAG_CASE);

        $this->filesystem->ensureDirectoryExists($this->languageFilePath);
        $this->filesystem->put(
            $file,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
            lock: true,
        );
    }

    private function groupPath(string $locale, string $group): string
    {
        return "{$this->languageFilePath}/{$locale}/{$group}.php";
    }

    /**
     * @param  array<array-key, mixed>  $array1
     * @param  array<array-key, mixed>  $array2
     * @return array<array-key, mixed>
     */
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
}
