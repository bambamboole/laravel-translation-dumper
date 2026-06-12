<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

interface TranslationWriter
{
    public function hasGroup(string $locale, string $group): bool;

    /** @param  array<string, mixed>  $translations */
    public function writeGroup(string $locale, string $group, array $translations): void;

    /** @param  array<string, string>  $translations */
    public function writeJson(string $locale, array $translations): void;
}
