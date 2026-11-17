<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

/**
 * Loads one translation catalog of one language.
 */
interface CatalogLoaderInterface
{
    /**
     * @return array<string,string> Message name => message; empty when the language has no such catalog.
     */
    public function load(LanguageInterface $language, string $catalogName): array;
}
