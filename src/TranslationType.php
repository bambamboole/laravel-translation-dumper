<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

enum TranslationType: string
{
    case JSON = 'json';
    case PHP = 'php';
}
