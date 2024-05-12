<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Console;

use Bambamboole\LaravelTranslationDumper\TranslationDumperInterface;
use Bambamboole\LaravelTranslationDumper\TranslationFinder;
use Illuminate\Console\Command;

class FindTranslationsCommand extends Command
{
    protected $signature = 'translations:find';

    protected $description = 'Find translations in PHP code and template files';

    public function handle(TranslationFinder $finder, TranslationDumperInterface $dumper): int
    {
        $translations = $finder->findTranslations();
        $this->info(sprintf('Found %s translations', count($translations)));
        $dumper->dump($translations);

        return self::SUCCESS;
    }
}
