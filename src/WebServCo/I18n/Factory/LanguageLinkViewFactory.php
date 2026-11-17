<?php

declare(strict_types=1);

namespace WebServCo\I18n\Factory;

use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\LanguageListInterface;
use WebServCo\I18n\Service\LanguageResolver;
use WebServCo\I18n\View\LanguageLinkView;

use function sprintf;

/**
 * Creates the language switch: one link per language, in the order of the list, with the current one marked.
 *
 * The URLs are query-only and relative (`?lang=de`): they keep the path of the current document, also after a script
 * changed it (eg. an htmx swap that pushed a new URL). Other query parameters are dropped.
 */
final class LanguageLinkViewFactory
{
    public function __construct(private LanguageListInterface $languageList)
    {
    }

    /**
     * @return list<\WebServCo\I18n\View\LanguageLinkView>
     */
    public function createLanguageLinks(LanguageInterface $currentLanguage): array
    {
        $result = [];

        foreach ($this->languageList->getLanguages() as $language) {
            $result[] = new LanguageLinkView(
                $language->getCode(),
                $language->getCode() === $currentLanguage->getCode(),
                $language->getNativeName(),
                sprintf('?%s=%s', LanguageResolver::QUERY_PARAMETER, $language->getCode()),
            );
        }

        return $result;
    }
}
