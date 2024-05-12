<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Feature\fixtures\code;

class TestClass
{
    public function get(): array
    {
        return [
            __('test.dotted.additional'),
            trans('test undotted key'),
            trans_choice('test'),
        ];
    }
}
