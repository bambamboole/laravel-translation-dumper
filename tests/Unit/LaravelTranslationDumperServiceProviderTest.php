<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Unit;

use Bambamboole\LaravelTranslationDumper\LaravelTranslationDumperServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LaravelTranslationDumperServiceProviderTest extends TestCase
{
    private Application|MockObject $app;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
    }

    public function test_it_does_only_publish_config_if_app_is_running_in_console(): void
    {
        $this->app
            ->expects($this->once())
            ->method('runningInConsole')
            ->willReturn(true);

        $this->app
            ->expects($this->once())
            ->method('configPath')
            ->willReturn('/foo/bar/config');

        $serviceProvider = $this->createServiceProvider();
        $serviceProvider->boot();

        self::assertArrayHasKey('config', $serviceProvider::$publishGroups);
        self::assertEquals(
            [
                LaravelTranslationDumperServiceProvider::class => [
                    dirname(__DIR__, 2).'/config/config.php' => '/foo/bar/config',
                ],
            ],
            $serviceProvider::$publishes,
        );
    }

    private function createServiceProvider(): LaravelTranslationDumperServiceProvider
    {
        return new LaravelTranslationDumperServiceProvider($this->app);
    }
}
