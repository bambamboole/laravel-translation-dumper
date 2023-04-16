<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;

class LaravelTranslationDumperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => $this->app->configPath('translation.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'translation');

        $this->app->singleton(
            TranslationDumper::class,
            static fn (Application $app) => new TranslationDumper(
                new Filesystem(),
                new ArrayExporter(),
                $app->langPath(),
                $app->make(Repository::class)->get('app.locale'),
            ),
        );

        $this->app->bind(
            TranslationDumperInterface::class,
            static fn (Application $app) => $app->make($app->make(Repository::class)->get('translation.dumper'))
        );

        $this->app->extend(
            'translator',
            static fn (Translator $translator, $app) => new DumpingTranslator(
                $translator,
                $app->make(TranslationDumperInterface::class),
            ),
        );
    }
}
