<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The language a request's user chose and the project stored (eg. a setting of a logged-in user).
 *
 * `LanguageResolver` asks it after `?lang=` and before the language cookie.
 */
interface PreferredLanguageProviderInterface
{
    /**
     * The stored language, or null when there is none (no user, no setting).
     * A language that is not in the language list is ignored.
     */
    public function getPreferredLanguage(ServerRequestInterface $request): ?LanguageInterface;
}
