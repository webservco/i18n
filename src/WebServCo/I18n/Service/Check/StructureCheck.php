<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use Override;
use WebServCo\I18n\Contract\CatalogCheckInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\DataTransfer\CatalogProblem;

use function array_diff;
use function array_filter;
use function array_intersect;
use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function in_array;
use function sprintf;

/**
 * Every language has a catalog directory, and every directory is a language. Every language has the catalogs of the
 * default language, and each of them has the message names of the default language: none less, none more.
 */
final class StructureCheck implements CatalogCheckInterface
{
    public function __construct(private CatalogReader $catalogReader)
    {
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    #[Override]
    public function check(): array
    {
        $result = [...$this->checkLanguageDirectories(), ...$this->checkOtherDirectories()];

        $defaultLanguage = $this->catalogReader->getLanguageList()->getDefaultLanguage();
        if ($this->catalogReader->getCatalogNames($defaultLanguage) === []) {
            $result[] = $this->createProblem($defaultLanguage->getCode(), null, 'The default language has no catalog.');
        }

        $directoryNames = $this->catalogReader->getDirectoryNames();
        foreach ($this->catalogReader->getLanguageList()->getLanguages() as $language) {
            // A language without directory is one problem, not one per catalog.
            if (
                $language->getCode() === $defaultLanguage->getCode()
                || !in_array($language->getCode(), $directoryNames, true)
            ) {
                continue;
            }

            $result = [...$result, ...$this->checkLanguage($language, $defaultLanguage)];
        }

        return $result;
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkCatalogNames(LanguageInterface $language, LanguageInterface $defaultLanguage): array
    {
        $expected = $this->catalogReader->getCatalogNames($defaultLanguage);
        $actual = $this->catalogReader->getCatalogNames($language);

        return [
            ...$this->createProblems(
                $language,
                array_diff($expected, $actual),
                'missing: the default language has it.',
            ),
            ...$this->createProblems(
                $language,
                array_diff($actual, $expected),
                'not in the default language.',
            ),
        ];
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkLanguage(LanguageInterface $language, LanguageInterface $defaultLanguage): array
    {
        return [
            ...$this->checkCatalogNames($language, $defaultLanguage),
            ...$this->checkMessageNames($language, $defaultLanguage),
        ];
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkLanguageDirectories(): array
    {
        $directoryNames = $this->catalogReader->getDirectoryNames();
        $result = [];

        foreach ($this->catalogReader->getLanguageList()->getLanguages() as $language) {
            if (in_array($language->getCode(), $directoryNames, true)) {
                continue;
            }

            $result[] = $this->createProblem($language->getCode(), null, 'No catalog directory.');
        }

        return $result;
    }

    /**
     * The message names of the catalogs that both languages have; a missing catalog is one problem, not one per name.
     *
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkMessageNames(LanguageInterface $language, LanguageInterface $defaultLanguage): array
    {
        $catalogNames = array_intersect(
            $this->catalogReader->getCatalogNames($defaultLanguage),
            $this->catalogReader->getCatalogNames($language),
        );
        $expected = $this->getKeys($defaultLanguage, $catalogNames);
        $actual = $this->getKeys($language, $catalogNames);
        $result = [];

        foreach (array_diff($expected, $actual) as $key) {
            $result[] = $this->createProblem(
                $language->getCode(),
                $key,
                'Message missing: the default language has it.',
            );
        }
        foreach (array_diff($actual, $expected) as $key) {
            $result[] = $this->createProblem($language->getCode(), $key, 'Message not in the default language.');
        }

        return $result;
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkOtherDirectories(): array
    {
        $codes = array_map(
            static function (LanguageInterface $language): string {
                return $language->getCode();
            },
            $this->catalogReader->getLanguageList()->getLanguages(),
        );
        $result = [];

        foreach (array_diff($this->catalogReader->getDirectoryNames(), $codes) as $directoryName) {
            $result[] = $this->createProblem(
                null,
                null,
                sprintf('Directory "%s" that is not the code of a language of the list.', $directoryName),
            );
        }

        return $result;
    }

    private function createProblem(?string $languageCode, ?string $key, string $description): CatalogProblem
    {
        return new CatalogProblem(self::class, $languageCode, $key, $description);
    }

    /**
     * @param array<string> $catalogNames
     * @param string $reason What is wrong with each catalog, after its name.
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function createProblems(LanguageInterface $language, array $catalogNames, string $reason): array
    {
        $result = [];

        foreach ($catalogNames as $catalogName) {
            $result[] = $this->createProblem(
                $language->getCode(),
                null,
                sprintf('Catalog "%s" %s', $catalogName, $reason),
            );
        }

        return $result;
    }

    /**
     * @param array<string> $catalogNames
     * @return list<string> The keys of the language in these catalogs.
     */
    private function getKeys(LanguageInterface $language, array $catalogNames): array
    {
        return array_values(
            array_filter(
                array_keys($this->catalogReader->getMessages($language)),
                static function (string $key) use ($catalogNames): bool {
                    return in_array(explode('.', $key, 2)[0], $catalogNames, true);
                },
            ),
        );
    }
}
