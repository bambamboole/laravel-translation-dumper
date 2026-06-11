<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests;

use Bambamboole\LaravelTranslationDumper\LaravelTranslationDumperServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $langPath;

    protected function setUp(): void
    {
        $this->langPath = sys_get_temp_dir().'/laravel-translation-dumper-tests/'.uniqid('lang-', true);
        (new Filesystem)->ensureDirectoryExists($this->langPath);

        // The provider decides whether to decorate the translator inside register(),
        // which runs before getEnvironmentSetUp(). The config default reads this env
        // var, so it has to be set before the application boots.
        putenv('DUMP_TRANSLATIONS=true');
        $_ENV['DUMP_TRANSLATIONS'] = $_SERVER['DUMP_TRANSLATIONS'] = 'true';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->langPath);

        putenv('DUMP_TRANSLATIONS');
        unset($_ENV['DUMP_TRANSLATIONS'], $_SERVER['DUMP_TRANSLATIONS']);

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->useLangPath($this->langPath);
    }

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelTranslationDumperServiceProvider::class,
        ];
    }
}
