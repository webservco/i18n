<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service;

use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
use InvalidArgumentException;
use NumberFormatter;
use Override;
use UnexpectedValueException;
use WebServCo\I18n\Contract\FormatterInterface;
use WebServCo\I18n\Contract\LanguageInterface;

use function round;
use function sprintf;

use const PHP_ROUND_HALF_UP;

/**
 * Formats dates, times and numbers in one language and time zone, with `IntlDateFormatter` and `NumberFormatter`.
 *
 * Formatters are created on first use and kept, one per style.
 */
final class Formatter implements FormatterInterface
{
    /**
     * The time zone of the date formatters: they get local times. ICU has time zone data of its own, often older than
     * PHP's: it refuses a zone it doesn't know, and shows another time where the rules of a zone have changed.
     */
    private const string LOCAL_TIME_ZONE = 'UTC';

    /**
     * Date formatters: "date type:time type" => formatter.
     *
     * @var array<string,\IntlDateFormatter>
     */
    private array $dateFormatters = [];

    /**
     * Number formatters: fraction digits => formatter.
     *
     * @var array<int,\NumberFormatter>
     */
    private array $numberFormatters = [];

    public function __construct(private LanguageInterface $language, private DateTimeZone $timeZone)
    {
    }

    #[Override]
    public function formatDate(DateTimeInterface $dateTime): string
    {
        return $this->formatDateTimeValue($dateTime, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
    }

    #[Override]
    public function formatDateTime(DateTimeInterface $dateTime): string
    {
        return $this->formatDateTimeValue($dateTime, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
    }

    #[Override]
    public function formatNumber(float|int $number, int $fractionDigits = 0): string
    {
        if ($fractionDigits < 0) {
            throw new InvalidArgumentException('The number of fraction digits can not be negative.');
        }

        // Rounded here, half up (the ICU default is half even: 2.5 would be 2).
        $value = round($number, $fractionDigits, PHP_ROUND_HALF_UP);
        if ($value === 0.0) {
            // No negative zero (`-0.00`).
            $value = 0.0;
        }

        $result = $this->getNumberFormatter($fractionDigits)->format($value);
        if ($result === false) {
            throw new UnexpectedValueException('Error formatting a number.');
        }

        return $result;
    }

    #[Override]
    public function formatTime(DateTimeInterface $dateTime): string
    {
        return $this->formatDateTimeValue($dateTime, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
    }

    #[Override]
    public function getTimeZone(): DateTimeZone
    {
        return $this->timeZone;
    }

    private function formatDateTimeValue(DateTimeInterface $dateTime, int $dateType, int $timeType): string
    {
        $dateFormatter = $this->dateFormatters[sprintf('%d:%d', $dateType, $timeType)]
            ??= new IntlDateFormatter($this->language->getLocale(), $dateType, $timeType, self::LOCAL_TIME_ZONE);

        // The value is converted here, by PHP: its local time, formatted as UTC.
        $result = $dateFormatter->format($dateTime->getTimestamp() + $this->timeZone->getOffset($dateTime));
        if ($result === false) {
            throw new UnexpectedValueException('Error formatting a date or time.');
        }

        return $result;
    }

    private function getNumberFormatter(int $fractionDigits): NumberFormatter
    {
        if (isset($this->numberFormatters[$fractionDigits])) {
            return $this->numberFormatters[$fractionDigits];
        }

        $numberFormatter = new NumberFormatter($this->language->getLocale(), NumberFormatter::DECIMAL);
        $numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
        $numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);

        return $this->numberFormatters[$fractionDigits] = $numberFormatter;
    }
}
