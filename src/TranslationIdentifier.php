<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Illuminate\Support\Str;

class TranslationIdentifier
{
    public static function identify(string $key): TranslationType
    {
        if (Str::contains($key, ' ')) {
            return TranslationType::JSON;
        }
        if (! Str::contains($key, '.')) {
            return TranslationType::JSON;
        }
        if (Str::endsWith($key, '.')) {
            return TranslationType::JSON;
        }

        return TranslationType::PHP;
    }
}
