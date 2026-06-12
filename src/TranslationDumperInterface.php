<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Bambamboole\LaravelTranslationDumper\DTO\Translation;

interface TranslationDumperInterface
{
    public function setLocale(string $locale): void;

    /**
     * @param  array<int, Translation>  $translations
     */
    public function dump(array $translations, ?string $group = null): void;
}
