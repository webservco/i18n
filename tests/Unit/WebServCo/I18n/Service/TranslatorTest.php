<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service;

use InvalidArgumentException;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tests\Unit\Assets\TestLanguage;
use Tests\Unit\Assets\ValueLanguage;
use UnexpectedValueException;
use WebServCo\Configuration\Service\PHPConfigurationLoader;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Translator;

use function dirname;
use function sprintf;

#[CoversClass(Translator::class)]
#[UsesClass(CatalogLoader::class)]
final class TranslatorTest extends TestCase
{
    public function testAMessageIsInTheLanguageOfTheTranslator(): void
    {
        $translator = $this->createTranslator(TestLanguage::German);

        self::assertSame(TestLanguage::German, $translator->getLanguage());
        self::assertSame('Keine Panik', $translator->translate('test.apostrophe'));
        self::assertSame("Don't panic", $this->createTranslator(TestLanguage::English)->translate('test.apostrophe'));
    }

    public function testParametersArePlacedAndPluralsAndNumbersFollowTheLanguage(): void
    {
        $translator = $this->createTranslator(TestLanguage::German);

        self::assertSame('Hallo, Ann!', $translator->translate('test.greeting', ['name' => 'Ann']));
        self::assertSame('1 Platte', $translator->translate('test.records', ['count' => 1]));
        self::assertSame('1.234 Platten', $translator->translate('test.records', ['count' => 1234]));
    }

    public function testAParameterIsNotEscaped(): void
    {
        self::assertSame(
            'Hello, <b>&</b>!',
            $this->createTranslator(TestLanguage::English)->translate('test.greeting', ['name' => '<b>&</b>']),
        );
    }

    public function testAMissingKeyFallsBackToTheDefaultLanguageAndIsLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(
            'Translation missing, using the default language.',
            ['key' => 'test.english_only', 'language' => 'de'],
        );

        self::assertSame(
            'Only in English',
            $this->createTranslator(TestLanguage::German, $logger)->translate('test.english_only'),
        );
    }

    public function testAFallbackMessageFollowsTheRulesOfTheDefaultLanguage(): void
    {
        // No Hungarian catalogs: every key falls back.
        self::assertSame(
            '1,234 records',
            $this->createTranslator(TestLanguage::Hungarian)->translate('test.records', ['count' => 1234]),
        );
    }

    public function testTheDefaultLanguageIsRecognisedByItsCode(): void
    {
        // Another object with the default's code: a missing key is not a fallback, so nothing is logged.
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $this->expectException(OutOfBoundsException::class);

        $this->createTranslator(new ValueLanguage('en', 'en_GB', 'English'), $logger)->translate('test.noexist');
    }

    public function testAKeyMissingInTheDefaultLanguageThrows(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessageIs('Translation key not found: "test.noexist".');

        $this->createTranslator(TestLanguage::English)->translate('test.noexist');
    }

    public function testAMessageThatCanNotBeFormattedThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageIs('Error formatting the message of "test.date_argument".');

        $this->createTranslator(TestLanguage::English)->translate('test.date_argument', ['when' => 'yesterday']);
    }

    public function testAKeyWithoutCatalogThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Invalid translation key: "test".');

        $this->createTranslator(TestLanguage::English)->translate('test');
    }

    public function testAnInvalidCatalogNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createTranslator(TestLanguage::English)->translate('../en/test.greeting');
    }

    private function createTranslator(LanguageInterface $language, ?LoggerInterface $logger = null): Translator
    {
        return new Translator(
            new CatalogLoader(new PHPConfigurationLoader(), sprintf('%s/Assets/I18n', dirname(__DIR__, 3))),
            $language,
            TestLanguage::DEFAULT,
            $logger ?? new NullLogger(),
        );
    }
}
