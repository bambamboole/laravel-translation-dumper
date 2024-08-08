<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\DTO;

class Translation
{
    public function __construct(
        public readonly string $key,
        public readonly string $translation,
        public readonly array $replace = [],
    ) {}
}
