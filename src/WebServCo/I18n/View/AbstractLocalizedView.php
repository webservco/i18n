<?php

declare(strict_types=1);

namespace WebServCo\I18n\View;

use DateTimeInterface;
use WebServCo\I18n\Contract\LocalizationContainerInterface;
use WebServCo\View\Contract\ViewInterface;
use WebServCo\View\View\AbstractView;

/**
 * A view whose template prints texts, dates, times or numbers in the language of the request:
 * `<?=$view->text('catalog.name')?>`, `<?=$view->date($dateTime)?>`.
 *
 * Every method returns text escaped for HTML.
 * The localization services are private, so they stay out of the JSON output.
 */
abstract class AbstractLocalizedView extends AbstractView implements ViewInterface
{
    public function __construct(private readonly LocalizationContainerInterface $localizationContainer)
    {
    }

    /**
     * Medium date, in the display time zone.
     */
    public function date(DateTimeInterface $dateTime): string
    {
        return $this->escapeText($this->localizationContainer->getFormatter()->formatDate($dateTime));
    }

    /**
     * Medium date and short time, in the display time zone.
     */
    public function dateTime(DateTimeInterface $dateTime): string
    {
        return $this->escapeText($this->localizationContainer->getFormatter()->formatDateTime($dateTime));
    }

    /**
     * Language code of the texts, for `lang` attributes.
     */
    public function languageCode(): string
    {
        return $this->localizationContainer->getTranslator()->getLanguage()->getCode();
    }

    /**
     * Decimal number with the grouping of the language.
     *
     * @param int $fractionDigits Digits after the decimal separator, always shown (0: an integer).
     */
    public function number(float|int $number, int $fractionDigits = 0): string
    {
        return $this->escapeText(
            $this->localizationContainer->getFormatter()->formatNumber($number, $fractionDigits),
        );
    }

    /**
     * Translated text: messages are plain text, parameters included.
     *
     * @param array<string,float|int|string> $parameters
     */
    public function text(string $key, array $parameters = []): string
    {
        return $this->escapeText($this->localizationContainer->getTranslator()->translate($key, $parameters));
    }

    /**
     * Short time, in the display time zone.
     */
    public function time(DateTimeInterface $dateTime): string
    {
        return $this->escapeText($this->localizationContainer->getFormatter()->formatTime($dateTime));
    }

    private function escapeText(string $text): string
    {
        return $this->escape($text) ?? '';
    }
}
