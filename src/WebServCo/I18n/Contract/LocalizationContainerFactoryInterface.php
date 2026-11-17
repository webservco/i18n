<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

use DateTimeZone;

/**
 * Creates the localization services of a language and a display time zone: for a request (with the language of
 * `RequestLanguageReaderInterface`), a command or an email.
 */
interface LocalizationContainerFactoryInterface
{
    public function createLocalizationContainer(
        LanguageInterface $language,
        DateTimeZone $timeZone,
    ): LocalizationContainerInterface;
}
