<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Illuminate\Support\Arr;

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

    /**
     * @param  Translation[]  $translations
     * @param  ?string  $group  When given, every translation is written into this
     *                          group file (created if needed), with keys relative
     *                          to the group (e.g. "entities/salesOrder").
     */
    public function dump(array $translations, ?string $group = null): void
    {
        if ($group !== null) {
            $this->dumpIntoGroup($translations, $group);

            return;
        }

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

    /**
     * Write every translation into one explicit group file, keys relative to it.
     *
     * @param  Translation[]  $translations
     */
    private function dumpIntoGroup(array $translations, string $group): void
    {
        $values = [];
        foreach ($translations as $translation) {
            Arr::set($values, $translation->key, $this->prepareTranslationValue($translation));
        }

        $this->writer->writeGroup($this->locale, $group, $values);
    }

    /** @param  Translation[]  $translations */
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

            $this->writer->writeGroup($this->locale, (string) $group, $values);
        }
    }

    /**
     * Decide which group a dotted key should be written to. By default the first
     * segment becomes a flat top level file (entities.salesOrder.title ->
     * entities). If a deeper nested file already exists we target that one
     * instead (entities/salesOrder), so existing nested files keep receiving
     * their keys while new nested files are never created implicitly.
     *
     * @return array{0: string, 1: string} the group path and the remaining dotted key
     */
    private function resolveTargetFile(string $dottedKey): array
    {
        $segments = explode('.', $dottedKey);

        $depth = 1;
        for ($candidateDepth = count($segments) - 1; $candidateDepth >= 2; $candidateDepth--) {
            $candidate = implode('/', array_slice($segments, 0, $candidateDepth));
            if ($this->writer->hasGroup($this->locale, $candidate)) {
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
}
