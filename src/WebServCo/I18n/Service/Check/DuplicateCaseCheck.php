<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use Override;
use WebServCo\I18n\Contract\CatalogCheckInterface;
use WebServCo\I18n\DataTransfer\CatalogProblem;

use function array_diff_assoc;
use function array_unique;
use function sprintf;

/**
 * No plural, selectordinal or select argument has a case twice: ICU uses the first, without an error.
 */
final class DuplicateCaseCheck implements CatalogCheckInterface
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
        $result = [];

        foreach ($this->catalogReader->getLanguageList()->getLanguages() as $language) {
            foreach ($this->catalogReader->getMessagePatterns($language) as $key => $messagePattern) {
                $result = [
                    ...$result,
                    ...$this->checkArguments($language->getCode(), $key, $messagePattern->complexArguments),
                ];
            }
        }

        return $result;
    }

    /**
     * @param list<\WebServCo\I18n\DataTransfer\ComplexArgument> $arguments
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkArguments(string $languageCode, string $key, array $arguments): array
    {
        $result = [];

        foreach ($arguments as $argument) {
            $duplicates = array_unique(array_diff_assoc($argument->selectors, array_unique($argument->selectors)));

            foreach ($duplicates as $selector) {
                $result[] = new CatalogProblem(
                    self::class,
                    $languageCode,
                    $key,
                    sprintf(
                        '{%s, %s}: case "%s" more than once (ICU uses the first).',
                        $argument->name,
                        $argument->type,
                        $selector,
                    ),
                );
            }
        }

        return $result;
    }
}
