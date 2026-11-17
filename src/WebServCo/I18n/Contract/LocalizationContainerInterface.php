<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

/**
 * The localization services in one language and time zone: texts, dates, times, numbers.
 */
interface LocalizationContainerInterface
{
    public function getFormatter(): FormatterInterface;

    public function getTranslator(): TranslatorInterface;
}
