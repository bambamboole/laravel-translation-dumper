<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

interface TranslationWriter
{
    /**
     * Whether a PHP group already exists for the locale. "group" is the path of
     * the file under the locale directory without the extension, e.g. "auth" or
     * "entities/salesOrder".
     */
    public function hasGroup(string $locale, string $group): bool;

    /**
     * Merge the given (nested) translations into the locale's PHP group file,
     * creating it if it does not exist yet, and persist it.
     *
     * @param  array<string, mixed>  $translations
     */
    public function writeGroup(string $locale, string $group, array $translations): void;

    /**
     * Merge the given translations into the locale's JSON file and persist it.
     *
     * @param  array<string, string>  $translations
     */
    public function writeJson(string $locale, array $translations): void;
}
