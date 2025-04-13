<?php declare(strict_types=1);

namespace Bambamboole\LaravelTranslationDumper\Tests\Unit;

use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\DumpingTranslator;
use Bambamboole\LaravelTranslationDumper\TranslationDumperInterface;
use Illuminate\Contracts\Translation\Translator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DumpingTranslatorTest extends TestCase
{
    private const TEST_LOCALE = 'de';

    private const TEST_KEY = 'foo.bar';

    private const TEST_REPLACEMENTS = ['buzz' => 'light year'];

    private const TEST_TRANSLATION = 'bar';

    private MockObject|Translator $translator;

    private MockObject|TranslationDumperInterface $translationDumper;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);
        $this->translationDumper = $this->createMock(TranslationDumperInterface::class);
    }

    public function test_it_proxies_get_as_expected(): void
    {
        $this->translator
            ->expects($this->once())
            ->method('get')
            ->with(self::TEST_KEY, self::TEST_REPLACEMENTS, self::TEST_LOCALE, false)
            ->willReturn(self::TEST_TRANSLATION);

        self::assertEquals(
            self::TEST_TRANSLATION,
            $this->createDumpingTranslator()->get(
                self::TEST_KEY,
                self::TEST_REPLACEMENTS,
                self::TEST_LOCALE,
                false,
            ),
        );
    }

    public function test_it_proxies_choice_as_expected(): void
    {
        $this->translator
            ->expects($this->once())
            ->method('choice')
            ->with(self::TEST_KEY, 3, self::TEST_REPLACEMENTS, self::TEST_LOCALE)
            ->willReturn(self::TEST_TRANSLATION);

        self::assertEquals(
            self::TEST_TRANSLATION,
            $this->createDumpingTranslator()->choice(
                self::TEST_KEY,
                3,
                self::TEST_REPLACEMENTS,
                self::TEST_LOCALE,
            ),
        );
    }

    public function test_it_proxies_get_locale_as_expected(): void
    {
        $this->translator
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn(self::TEST_LOCALE);

        self::assertEquals(self::TEST_LOCALE, $this->createDumpingTranslator()->getLocale());
    }

    public function test_it_proxies_set_locale_as_expected(): void
    {
        $this->translationDumper
            ->expects($this->once())
            ->method('setLocale')
            ->with(self::TEST_LOCALE);
        $this->translator
            ->expects($this->once())
            ->method('setLocale')
            ->with(self::TEST_LOCALE);

        $this->createDumpingTranslator()->setLocale('de');
    }

    public function test_it_calls_the_translation_dumper_on_destruct(): void
    {
        $this->translator
            ->expects($this->once())
            ->method('get')
            ->with(self::TEST_KEY)
            ->willReturn(self::TEST_KEY);
        $this->translationDumper
            ->expects($this->once())
            ->method('dump')
            ->with(self::callback(
                fn (array $translations) => $translations[0] instanceof Translation && $translations[0]->key === self::TEST_KEY,
            ));

        $dumpingTranslator = $this->createDumpingTranslator();
        $dumpingTranslator->get(self::TEST_KEY);

        unset($dumpingTranslator);
    }

    private function createDumpingTranslator(): DumpingTranslator
    {
        return new DumpingTranslator(
            $this->translator,
            $this->translationDumper,
        );
    }
}
