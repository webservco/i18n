<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\TestLanguage;
use WebServCo\I18n\Service\Formatter;

/**
 * Expected values are mostly German: its patterns are the same in every recent ICU version.
 */
#[CoversClass(Formatter::class)]
final class FormatterTest extends TestCase
{
    public function testDatesAndTimesAreShownInTheTimeZoneOfTheFormatter(): void
    {
        $formatter = $this->createFormatter(TestLanguage::German, 'Europe/Budapest');
        // 23:30 UTC is 00:30 of the next day in Budapest (UTC+1 in November).
        $dateTime = new DateTimeImmutable('2026-11-05 23:30:00', new DateTimeZone('UTC'));

        self::assertSame('06.11.2026', $formatter->formatDate($dateTime));
        self::assertSame('00:30', $formatter->formatTime($dateTime));
        self::assertSame('06.11.2026, 00:30', $formatter->formatDateTime($dateTime));
        self::assertSame('Europe/Budapest', $formatter->getTimeZone()->getName());
    }

    public function testTheTimeZoneOfTheValueDoesNotMatter(): void
    {
        $formatter = $this->createFormatter(TestLanguage::German, 'UTC');
        $dateTime = new DateTimeImmutable('2026-11-06 00:30:00', new DateTimeZone('Europe/Budapest'));

        self::assertSame('05.11.2026, 23:30', $formatter->formatDateTime($dateTime));
    }

    public function testDatesFollowTheLanguage(): void
    {
        $dateTime = new DateTimeImmutable('2026-11-05 12:00:00', new DateTimeZone('UTC'));

        $german = $this->createFormatter(TestLanguage::German, 'UTC')->formatDate($dateTime);

        self::assertSame('05.11.2026', $german);
        self::assertNotSame($german, $this->createFormatter(TestLanguage::English, 'UTC')->formatDate($dateTime));
        self::assertNotSame($german, $this->createFormatter(TestLanguage::Hungarian, 'UTC')->formatDate($dateTime));
    }

    public function testEnglishHasEuropeanFormats(): void
    {
        $formatter = $this->createFormatter(TestLanguage::English, 'UTC');
        $dateTime = new DateTimeImmutable('2026-11-05 21:07:00', new DateTimeZone('UTC'));

        self::assertSame('5 Nov 2026', $formatter->formatDate($dateTime));
        self::assertSame('21:07', $formatter->formatTime($dateTime));
        self::assertSame('5 Nov 2026, 21:07', $formatter->formatDateTime($dateTime));
    }

    public function testNumbersHaveTheGroupingAndDecimalSeparatorOfTheLanguage(): void
    {
        self::assertSame('1,234,567', $this->createFormatter(TestLanguage::English, 'UTC')->formatNumber(1234567));
        self::assertSame('1.234.567', $this->createFormatter(TestLanguage::German, 'UTC')->formatNumber(1234567));
        self::assertSame('1.234,50', $this->createFormatter(TestLanguage::German, 'UTC')->formatNumber(1234.5, 2));
    }

    public function testNumbersAreRoundedHalfUpToTheFractionDigits(): void
    {
        $formatter = $this->createFormatter(TestLanguage::English, 'UTC');

        self::assertSame('3', $formatter->formatNumber(2.5));
        // 1.125 is exact in binary: half even would give 1.12.
        self::assertSame('1.13', $formatter->formatNumber(1.125, 2));
        self::assertSame('7.0', $formatter->formatNumber(7, 1));
    }

    public function testANumberRoundedToZeroHasNoMinusSign(): void
    {
        $formatter = $this->createFormatter(TestLanguage::English, 'UTC');

        self::assertSame('0.00', $formatter->formatNumber(-0.004, 2));
        self::assertSame('0', $formatter->formatNumber(-0.4));
        self::assertSame('-1', $formatter->formatNumber(-0.5));
    }

    public function testNegativeFractionDigitsAreRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The number of fraction digits can not be negative.');

        $this->createFormatter(TestLanguage::English, 'UTC')->formatNumber(1, -1);
    }

    /**
     * @param non-empty-string $timeZone
     */
    private function createFormatter(TestLanguage $language, string $timeZone): Formatter
    {
        return new Formatter($language, new DateTimeZone($timeZone));
    }
}
