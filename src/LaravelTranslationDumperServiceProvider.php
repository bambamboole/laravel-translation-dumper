<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;

class LaravelTranslationDumperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/config.php' => $this->app->configPath('translation.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'translation');

        if (config('translation.dump_translations')) {
            $this->app->singleton(
                TranslationWriter::class,
                static fn (Application $app) => new FileTranslationWriter(
                    $app->make(Filesystem::class),
                    $app->langPath(),
                    static fn (): array => $app->make('translation.loader')->namespaces(),
                ),
            );

            $this->app->singleton(
                TranslationDumper::class,
                static fn (Application $app) => new TranslationDumper(
                    $app->make(TranslationWriter::class),
                    config('app.locale'),
                ),
            );

            $this->app->bind(
                TranslationDumperInterface::class,
                static fn (Application $app) => $app->make(config('translation.dumper'))
            );

            $this->app->extend(
                'translator',
                static fn (Translator $translator, $app) => new DumpingTranslator(
                    $translator,
                    $app->make(TranslationDumperInterface::class),
                    $app->make(LoggerInterface::class),
                    config('translation.dump_prefix', 'x-'),
                    config('translation.ignore_keys', []),
                    config('translation.dump_non_dotted_keys', false),
                    static fn (): array => config('translation.dump_namespaces', []),
                ),
            );
        }
    }
}
