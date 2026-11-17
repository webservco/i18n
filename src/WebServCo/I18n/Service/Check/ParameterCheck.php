<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use Override;
use WebServCo\I18n\Contract\CatalogCheckInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\DataTransfer\MessagePattern;

use function array_map;
use function array_unique;
use function array_values;
use function json_encode;
use function ksort;
use function sort;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Every message has the parameters of the default language: the same argument names and types, and the same select
 * cases. A translation that loses `{count}`, or turns a plural into a plain number, is reported.
 *
 * A key the default language lacks is left to `StructureCheck`, and a message the checks can't read to
 * `MessageSyntaxCheck`.
 */
final class ParameterCheck implements CatalogCheckInterface
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
        $defaultLanguage = $this->catalogReader->getLanguageList()->getDefaultLanguage();
        $expected = array_map($this->getParameters(...), $this->catalogReader->getMessagePatterns($defaultLanguage));
        $result = [];

        foreach ($this->catalogReader->getLanguageList()->getLanguages() as $language) {
            if ($language->getCode() === $defaultLanguage->getCode()) {
                continue;
            }

            $result = [...$result, ...$this->checkLanguage($language, $expected)];
        }

        return $result;
    }

    /**
     * @param array<string,string> $expected Key => the parameters of the default language.
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkLanguage(LanguageInterface $language, array $expected): array
    {
        $result = [];

        foreach ($this->catalogReader->getMessagePatterns($language) as $key => $messagePattern) {
            $parameters = $this->getParameters($messagePattern);
            if (!isset($expected[$key]) || $parameters === $expected[$key]) {
                continue;
            }

            $result[] = new CatalogProblem(
                self::class,
                $language->getCode(),
                $key,
                sprintf('Parameters %s, not %s as in the default language.', $parameters, $expected[$key]),
            );
        }

        return $result;
    }

    /**
     * Argument names and types, and the cases of each select argument, as JSON: sorted, so equal parameters are the
     * same string.
     */
    private function getParameters(MessagePattern $messagePattern): string
    {
        return json_encode(
            ['arguments' => $messagePattern->arguments, 'selectCases' => $this->getSelectCases($messagePattern)],
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array<string,list<string>> Select argument name => its cases, sorted, each once.
     */
    private function getSelectCases(MessagePattern $messagePattern): array
    {
        $result = [];

        foreach ($messagePattern->complexArguments as $argument) {
            if ($argument->type !== 'select') {
                continue;
            }

            $result[$argument->name] = [...$result[$argument->name] ?? [], ...$argument->selectors];
        }

        foreach ($result as $name => $selectors) {
            $selectors = array_values(array_unique($selectors));
            sort($selectors);
            $result[$name] = $selectors;
        }
        ksort($result);

        return $result;
    }
}
