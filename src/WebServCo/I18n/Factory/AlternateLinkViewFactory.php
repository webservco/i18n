<?php

declare(strict_types=1);

namespace WebServCo\I18n\Factory;

use WebServCo\I18n\Contract\LanguageListInterface;
use WebServCo\I18n\Service\LanguageResolver;
use WebServCo\I18n\View\AlternateLinkView;

use function sprintf;
use function strcspn;
use function substr;

/**
 * Creates the language versions of a page for search engines (`hreflang`): the page URL with `?lang={code}` for each
 * language, in the order of the list, and the page URL alone as `x-default` (the language of the browser decides).
 *
 * Only for indexable pages. The query and the fragment of the page URL are dropped: an indexable page is its path.
 */
final class AlternateLinkViewFactory
{
    public const string HREFLANG_DEFAULT = 'x-default';

    public function __construct(private LanguageListInterface $languageList)
    {
    }

    /**
     * @param string $pageUrl Absolute (scheme, host, path): search engines need the full URL.
     * @return list<\WebServCo\I18n\View\AlternateLinkView>
     */
    public function createAlternateLinks(string $pageUrl): array
    {
        $url = substr($pageUrl, 0, strcspn($pageUrl, '?#'));
        $result = [];

        foreach ($this->languageList->getLanguages() as $language) {
            $result[] = new AlternateLinkView(
                $language->getCode(),
                sprintf('%s?%s=%s', $url, LanguageResolver::QUERY_PARAMETER, $language->getCode()),
            );
        }
        $result[] = new AlternateLinkView(self::HREFLANG_DEFAULT, $url);

        return $result;
    }
}
