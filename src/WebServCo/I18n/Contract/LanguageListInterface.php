<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

/**
 * The languages of the project, and the default one.
 */
interface LanguageListInterface
{
    /**
     * The language with this code, or null when there is none.
     */
    public function findByCode(string $code): ?LanguageInterface;

    /**
     * The reference language: its catalogs have every key, and a key missing in another language falls back to it.
     */
    public function getDefaultLanguage(): LanguageInterface;

    /**
     * @return non-empty-list<\WebServCo\I18n\Contract\LanguageInterface> In the order of the language switch.
     */
    public function getLanguages(): array;
}
