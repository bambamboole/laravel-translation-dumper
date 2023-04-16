<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

interface TranslationDumperInterface
{
    public function setLocale(string $locale): void;

    public function dump(array $translationKeys): void;
}
