<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

use DateTimeInterface;
use DateTimeZone;

/**
 * Formats dates, times and numbers in one language.
 *
 * Dates and times are shown in the display time zone, whatever the time zone of the value.
 * Results are plain text, not escaped.
 */
interface FormatterInterface
{
    /**
     * Medium date: `5 Nov 2026`, `05.11.2026`, `2026. nov. 5.`.
     */
    public function formatDate(DateTimeInterface $dateTime): string;

    /**
     * Medium date and short time: `5 Nov 2026, 21:07`, `05.11.2026, 21:07`.
     */
    public function formatDateTime(DateTimeInterface $dateTime): string;

    /**
     * Decimal number with the grouping of the language, rounded half up: `1,234.50`, `1.234,50`.
     *
     * @param int $fractionDigits Digits after the decimal separator, always shown (0: an integer).
     */
    public function formatNumber(float|int $number, int $fractionDigits = 0): string;

    /**
     * Short time: `21:07`.
     */
    public function formatTime(DateTimeInterface $dateTime): string;

    /**
     * The time zone dates and times are shown in.
     */
    public function getTimeZone(): DateTimeZone;
}
