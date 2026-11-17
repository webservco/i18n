<?php

declare(strict_types=1);

namespace WebServCo\I18n\Enum;

/**
 * Where the language of a request comes from (`LanguageResolver`), in the order they are checked.
 */
enum LanguageSource
{
    // `?lang=`: also stored in the language cookie.
    case Query;

    // The language the user chose and the project stored (`PreferredLanguageProviderInterface`).
    case Preference;

    // The language cookie.
    case Cookie;

    // The browser's `Accept-Language` header.
    case AcceptLanguage;

    // None of the above: the default language.
    case Fallback;
}
