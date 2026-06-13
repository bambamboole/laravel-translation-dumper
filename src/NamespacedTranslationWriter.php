<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

interface NamespacedTranslationWriter
{
    public function hasNamespacedGroup(string $locale, string $namespace, string $group): bool;

    /** @param  array<string, mixed>  $translations */
    public function writeNamespacedGroup(string $locale, string $namespace, string $group, array $translations): void;
}
