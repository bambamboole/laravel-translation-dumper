<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Illuminate\Contracts\Translation\Translator as TranslatorInterface;

class DumpingTranslator implements TranslatorInterface
{
    private array $keysWithMissingTranslations = [];

    private array $missingTranslations = [];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly TranslationDumperInterface $translationDumper,
        private readonly string $dumpPrefix = 'x-',
        private readonly array $ignoreKeys = [],
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

        $this->translationDumper->dump($this->missingTranslations);
    }

    private function shouldBeIgnored(string $key): bool
    {
        foreach ($this->ignoreKeys as $ignoreKey) {
            if (str_contains($key, $ignoreKey)) {
                return true;
            }
        }

        return false;
    }
}
