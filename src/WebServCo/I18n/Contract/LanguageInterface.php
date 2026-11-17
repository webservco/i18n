<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

/**
 * A language of the project. Any class can describe one; an enum is the usual choice.
 *
 * Languages are compared by code, never by identity.
 */
interface LanguageInterface
{
    /**
     * The language code: a primary language subtag (`de`), never a region (`de-AT`).
     * It names the catalog directory and is the `?lang=` value, the `lang` attribute and `Content-Language`.
     */
    public function getCode(): string;

    /**
     * The ICU locale of messages, dates, times and numbers (eg. `en_GB` for European formats in English).
     */
    public function getLocale(): string;

    /**
     * The name of the language in the language itself (language switch); not translated.
     */
    public function getNativeName(): string;
}
