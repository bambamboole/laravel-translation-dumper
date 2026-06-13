<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Illuminate\Support\Arr;
use LogicException;

class TranslationDumper implements TranslationDumperInterface
{
    public function __construct(
        private readonly TranslationWriter $writer,
        private string $locale,
    ) {}

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /** @param  Translation[]  $translations */
    public function dump(array $translations, ?string $group = null): void
    {
        if ($group !== null) {
            $this->dumpIntoGroup($translations, $group);

            return;
        }

        $jsonTranslations = [];
        $phpTranslations = [];
        foreach ($translations as $translation) {
            [, $key] = $this->parseNamespacedKey($translation->key);

            if (TranslationIdentifier::identify($key) === TranslationType::PHP) {
                $phpTranslations[] = $translation;
            } else {
                $jsonTranslations[] = $translation;
            }
        }

        $this->dumpPhpTranslations($phpTranslations);
        $this->dumpJsonTranslations($jsonTranslations);
    }

    /** @param  Translation[]  $translations */
    private function dumpIntoGroup(array $translations, string $group): void
    {
        $values = [];
        $namespace = null;

        foreach ($translations as $translation) {
            [$translationNamespace, $key] = $this->parseNamespacedKey($translation->key);
            $namespace ??= $translationNamespace;

            Arr::set($values, $key, $this->prepareTranslationValue($translation));
        }

        $this->writeGroup($group, $values, $namespace);
    }

    /** @param  Translation[]  $translations */
    private function dumpPhpTranslations(array $translations): void
    {
        $byFile = [];
        $targetNamespaces = [];

        foreach ($translations as $translation) {
            [$namespace, $key] = $this->parseNamespacedKey($translation->key);
            [$group, $remainingKey] = $this->resolveTargetFile($key, $namespace);
            $namespaceKey = $namespace ?? '';

            $targetNamespaces[$namespaceKey] = $namespace;
            $byFile[$namespaceKey][$group][] = [$remainingKey, $this->prepareTranslationValue($translation)];
        }

        foreach ($byFile as $namespaceKey => $groups) {
            foreach ($groups as $group => $entries) {
                $values = [];
                foreach ($entries as [$remainingKey, $value]) {
                    Arr::set($values, $remainingKey, $value);
                }

                $this->writeGroup((string) $group, $values, $targetNamespaces[$namespaceKey]);
            }
        }
    }

    /** @return array{0: string, 1: string} */
    private function resolveTargetFile(string $dottedKey, ?string $namespace = null): array
    {
        $segments = explode('.', $dottedKey);

        $depth = 1;
        for ($candidateDepth = count($segments) - 1; $candidateDepth >= 2; $candidateDepth--) {
            $candidate = implode('/', array_slice($segments, 0, $candidateDepth));
            if ($this->hasGroup($candidate, $namespace)) {
                $depth = $candidateDepth;
                break;
            }
        }

        return [
            implode('/', array_slice($segments, 0, $depth)),
            implode('.', array_slice($segments, $depth)),
        ];
    }

    /** @param  Translation[]  $translations */
    private function dumpJsonTranslations(array $translations): void
    {
        $values = [];
        foreach ($translations as $translation) {
            $values[$translation->key] = $this->prepareTranslationValue($translation);
        }

        $this->writer->writeJson($this->locale, $values);
    }

    private function prepareTranslationValue(Translation $translation): string
    {
        $value = $translation->translation;
        foreach ($translation->replace as $key => $replace) {
            $value .= sprintf(' :%s %s', $key, $replace ?? 'n/a');
        }

        return $value;
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

    private function hasGroup(string $group, ?string $namespace): bool
    {
        if ($namespace === null) {
            return $this->writer->hasGroup($this->locale, $group);
        }

        return $this->writer instanceof NamespacedTranslationWriter
            && $this->writer->hasNamespacedGroup($this->locale, $namespace, $group);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function writeGroup(string $group, array $values, ?string $namespace): void
    {
        if ($namespace === null) {
            $this->writer->writeGroup($this->locale, $group, $values);

            return;
        }

        if (! $this->writer instanceof NamespacedTranslationWriter) {
            throw new LogicException("Translation writer cannot write namespace [{$namespace}].");
        }

        $this->writer->writeNamespacedGroup($this->locale, $namespace, $group, $values);
    }
}
