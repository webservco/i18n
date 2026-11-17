<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Factory;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Unit\Assets\TestLanguage;
use WebServCo\Configuration\Service\PHPConfigurationLoader;
use WebServCo\I18n\Container\LocalizationContainer;
use WebServCo\I18n\Factory\LocalizationContainerFactory;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Formatter;
use WebServCo\I18n\Service\Translator;

use function dirname;
use function sprintf;

#[CoversClass(LocalizationContainer::class)]
#[CoversClass(LocalizationContainerFactory::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(Formatter::class)]
#[UsesClass(Translator::class)]
final class LocalizationContainerFactoryTest extends TestCase
{
    public function testTheTranslatorIsInTheGivenLanguage(): void
    {
        $translator = $this->createLocalizationContainerFactory()
            ->createLocalizationContainer(TestLanguage::German, new DateTimeZone('UTC'))
            ->getTranslator();

        self::assertSame(TestLanguage::German, $translator->getLanguage());
        self::assertSame('Hallo, Ann!', $translator->translate('test.greeting', ['name' => 'Ann']));
    }

    public function testTheTranslatorFallsBackToTheDefaultLanguage(): void
    {
        $translator = $this->createLocalizationContainerFactory()
            ->createLocalizationContainer(TestLanguage::Hungarian, new DateTimeZone('UTC'))
            ->getTranslator();

        self::assertSame('Only in English', $translator->translate('test.english_only'));
    }

    public function testTheFormatterIsInTheGivenLanguageAndTimeZone(): void
    {
        $formatter = $this->createLocalizationContainerFactory()
            ->createLocalizationContainer(TestLanguage::German, new DateTimeZone('Europe/Budapest'))
            ->getFormatter();

        self::assertSame('1.234', $formatter->formatNumber(1234));
        self::assertSame('Europe/Budapest', $formatter->getTimeZone()->getName());
        // 23:30 UTC is 00:30 of the next day in Budapest (UTC+1 in November).
        self::assertSame(
            '00:30',
            $formatter->formatTime(new DateTimeImmutable('2026-11-05 23:30:00', new DateTimeZone('UTC'))),
        );
    }

    private function createLocalizationContainerFactory(): LocalizationContainerFactory
    {
        return new LocalizationContainerFactory(
            new CatalogLoader(new PHPConfigurationLoader(), sprintf('%s/Assets/I18n', dirname(__DIR__, 3))),
            TestLanguage::DEFAULT,
            new NullLogger(),
        );
    }
}
