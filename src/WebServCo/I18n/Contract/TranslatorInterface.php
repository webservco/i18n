<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

/**
 * Translates message keys into one language.
 */
interface TranslatorInterface
{
    public function getLanguage(): LanguageInterface;

    /**
     * The message of a key (`{catalog}.{name}`), formatted with ICU MessageFormat: plain text, not escaped.
     *
     * @param array<string,float|int|string> $parameters The message arguments, by name.
     */
    public function translate(string $key, array $parameters = []): string;
}
