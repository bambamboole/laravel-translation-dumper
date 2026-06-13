<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Closure;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

class FileTranslationWriter implements NamespacedTranslationWriter, TranslationWriter
{
    /**
     * @param  array<string, string>|Closure(): array<string, string>  $namespacePaths
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $languageFilePath,
        private readonly array|Closure $namespacePaths = [],
    ) {}

    public function hasGroup(string $locale, string $group): bool
    {
        return $this->filesystem->exists($this->groupPath($locale, $group));
    }

    public function hasNamespacedGroup(string $locale, string $namespace, string $group): bool
    {
        return $this->filesystem->exists($this->groupPath($locale, $group, $this->namespacePath($namespace)));
    }

    public function writeGroup(string $locale, string $group, array $translations): void
    {
        $this->writeGroupFile($this->languageFilePath, $locale, $group, $translations);
    }

    public function writeNamespacedGroup(string $locale, string $namespace, string $group, array $translations): void
    {
        $this->writeGroupFile($this->namespacePath($namespace), $locale, $group, $translations);
    }

    private function writeGroupFile(string $languageFilePath, string $locale, string $group, array $translations): void
    {
        if ($translations === []) {
            return;
        }

        $file = $this->groupPath($locale, $group, $languageFilePath);
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

    private function groupPath(string $locale, string $group, ?string $languageFilePath = null): string
    {
        $languageFilePath ??= $this->languageFilePath;

        return rtrim($languageFilePath, '/')."/{$locale}/{$group}.php";
    }

    private function namespacePath(string $namespace): string
    {
        $namespacePaths = $this->namespacePaths();

        if (! isset($namespacePaths[$namespace])) {
            throw new InvalidArgumentException("Translation namespace [{$namespace}] is not registered.");
        }

        return $namespacePaths[$namespace];
    }

    /**
     * @return array<string, string>
     */
    private function namespacePaths(): array
    {
        if (! $this->namespacePaths instanceof Closure) {
            return $this->namespacePaths;
        }

        /** @var array<string, string> $namespacePaths */
        $namespacePaths = ($this->namespacePaths)();

        return $namespacePaths;
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
