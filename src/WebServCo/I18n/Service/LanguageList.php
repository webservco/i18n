<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service;

use InvalidArgumentException;
use Override;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\LanguageListInterface;

use function array_values;
use function preg_match;
use function sprintf;

/**
 * The languages of the project, and the default one: `new LanguageList(Language::cases(), Language::DEFAULT)`.
 *
 * Refuses a code that is not a primary language subtag, a code given twice, and a default language not in the list.
 * Languages are compared by code: every language it returns, the default too, is an object of the list.
 */
final class LanguageList implements LanguageListInterface
{
    /**
     * A primary language subtag (BCP 47): two or three lowercase letters (`de`, `ast`).
     */
    public const string CODE_PATTERN = '/^[a-z]{2,3}$/';

    private LanguageInterface $defaultLanguage;

    /**
     * Language code => language, in the order given.
     *
     * @var non-empty-array<string,\WebServCo\I18n\Contract\LanguageInterface>
     */
    private array $languages;

    /**
     * @param array<\WebServCo\I18n\Contract\LanguageInterface> $languages In the order of the language switch.
     */
    public function __construct(array $languages, LanguageInterface $defaultLanguage)
    {
        $languagesByCode = $this->indexByCode($languages);

        $defaultCode = $defaultLanguage->getCode();
        if (!isset($languagesByCode[$defaultCode])) {
            throw new InvalidArgumentException(sprintf('The default language is not in the list: "%s".', $defaultCode));
        }

        $this->defaultLanguage = $languagesByCode[$defaultCode];
        $this->languages = $languagesByCode;
    }

    #[Override]
    public function findByCode(string $code): ?LanguageInterface
    {
        return $this->languages[$code] ?? null;
    }

    #[Override]
    public function getDefaultLanguage(): LanguageInterface
    {
        return $this->defaultLanguage;
    }

    /**
     * @return non-empty-list<\WebServCo\I18n\Contract\LanguageInterface>
     */
    #[Override]
    public function getLanguages(): array
    {
        return array_values($this->languages);
    }

    /**
     * @param array<\WebServCo\I18n\Contract\LanguageInterface> $languages
     * @return array<string,\WebServCo\I18n\Contract\LanguageInterface> Language code => language, in the order given.
     */
    private function indexByCode(array $languages): array
    {
        $result = [];

        foreach ($languages as $language) {
            $code = $language->getCode();
            if (preg_match(self::CODE_PATTERN, $code) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid language code: "%s".', $code));
            }
            if (isset($result[$code])) {
                throw new InvalidArgumentException(sprintf('Language code given twice: "%s".', $code));
            }
            $result[$code] = $language;
        }

        return $result;
    }
}
