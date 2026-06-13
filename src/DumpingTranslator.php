<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Closure;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;

class DumpingTranslator implements TranslatorContract
{
    private array $keysWithMissingTranslations = [];

    private array $missingTranslations = [];

    public function __construct(
        private readonly Translator $translator,
        private readonly TranslationDumperInterface $translationDumper,
        private readonly LoggerInterface $logger,
        private readonly string $dumpPrefix = 'x-',
        private readonly array $ignoreKeys = [],
        private readonly bool $dumpNonDottedKeys = false,
        private readonly array|Closure $dumpNamespaces = [],
    ) {}

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $translation = $this->translator->get($key, $replace, $locale, $fallback);
        if ($translation === $key && ! $this->shouldBeIgnored($key)) {
            $this->keysWithMissingTranslations[] = $key;
            $this->missingTranslations[] = new Translation($key, $this->dumpPrefix.$key, $replace);
        }

        return $translation;
    }

    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        return $this->translator->choice($key, $number, $replace, $locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setLocale($locale): void
    {
        $this->translationDumper->setLocale($locale);
        $this->translator->setLocale($locale);
    }

    public function __call($method, $parameters)
    {
        return $this->translator->$method(...$parameters);
    }

    public function __destruct()
    {
        if (count($this->keysWithMissingTranslations) === 0) {
            return;
        }

        try {
            $this->translationDumper->dump($this->missingTranslations);
        } catch (\Throwable $e) {
            $this->logDumpFailure($e);
        }
    }

    private function logDumpFailure(\Throwable $e): void
    {
        try {
            $this->logger->error('Failed to dump missing translations.', [
                'exception' => $e,
            ]);
        } catch (\Throwable) {
        }
    }

    private function shouldBeIgnored(string $key): bool
    {
        [$namespace, $translationKey] = $this->parseNamespacedKey($key);

        if ($namespace !== null && ! in_array($namespace, $this->dumpNamespaces(), true)) {
            return true;
        }

        if ($namespace !== null && TranslationIdentifier::identify($translationKey) === TranslationType::JSON) {
            return true;
        }

        if (! $this->dumpNonDottedKeys && TranslationIdentifier::identify($translationKey) === TranslationType::JSON) {
            return true;
        }

        foreach ($this->ignoreKeys as $ignoreKey) {
            if (str_contains($key, $ignoreKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function dumpNamespaces(): array
    {
        if (! $this->dumpNamespaces instanceof Closure) {
            return $this->dumpNamespaces;
        }

        /** @var array<int, string> $dumpNamespaces */
        $dumpNamespaces = ($this->dumpNamespaces)();

        return $dumpNamespaces;
    }

    /** @return array{0: string|null, 1: string} */
    private function parseNamespacedKey(string $key): array
    {
        if (! str_contains($key, '::')) {
            return [null, $key];
        }

        [$namespace, $key] = explode('::', $key, 2);

        return [$namespace, $key];
    }
}
