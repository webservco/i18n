<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use MessageFormatter;
use Override;
use WebServCo\I18n\Contract\CatalogCheckInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\DataTransfer\ComplexArgument;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function implode;
use function in_array;
use function is_string;
use function range;
use function sort;
use function sprintf;
use function str_starts_with;

/**
 * Every plural (`plural`, `selectordinal`) has exactly the categories ICU uses for whole numbers in the message's
 * language. ICU doesn't check them: a missing one falls back to `other` without an error (Romanian `few`, Spanish
 * `many`), and one the language doesn't have is never used (English `zero`: write `=0`). Exact values (`=0`) are free.
 *
 * The categories come from ICU, so a new language needs no change.
 */
final class PluralCategoryCheck implements CatalogCheckInterface
{
    /**
     * The plural categories of CLDR, and the argument types that select one.
     */
    private const array PLURAL_CATEGORIES = ['zero', 'one', 'two', 'few', 'many', 'other'];
    private const array PLURAL_TYPES = ['plural', 'selectordinal'];

    /**
     * With 0 to 1000, the whole numbers that show every plural category of a language: the rules look at the last
     * digits, or at whole millions (Spanish `many`).
     */
    private const array SAMPLE_LARGE_NUMBERS = [10_000, 100_000, 1_000_000, 10_000_000, 1_000_000_000];

    /**
     * Plural categories, per ICU locale and argument type (cache).
     *
     * @var array<string,list<string>>
     */
    private array $pluralCategories = [];

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
                $result = [...$result, ...$this->checkArguments($language, $key, $messagePattern->complexArguments)];
            }
        }

        return $result;
    }

    /**
     * @param list<\WebServCo\I18n\DataTransfer\ComplexArgument> $arguments
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkArguments(LanguageInterface $language, string $key, array $arguments): array
    {
        $result = [];

        foreach ($arguments as $argument) {
            $description = $this->findProblem($language, $argument);
            if ($description === null) {
                continue;
            }

            $result[] = new CatalogProblem(self::class, $language->getCode(), $key, $description);
        }

        return $result;
    }

    /**
     * The categories ICU uses for whole numbers in the language: a message with a case for each CLDR category,
     * formatted with sample numbers, prints the category of each.
     *
     * @return list<string> Sorted.
     */
    private function findPluralCategories(LanguageInterface $language, string $type): array
    {
        $cases = array_map(
            static function (string $category): string {
                return sprintf('%1$s {%1$s}', $category);
            },
            self::PLURAL_CATEGORIES,
        );
        $messageFormatter = new MessageFormatter(
            $language->getLocale(),
            sprintf('{number, %s, %s}', $type, implode(' ', $cases)),
        );

        $result = [];
        foreach ([...range(0, 1000), ...self::SAMPLE_LARGE_NUMBERS] as $number) {
            $category = $messageFormatter->format(['number' => $number]);
            // A formatting error is an empty category, which no plural has: every plural of the language is reported.
            $result[is_string($category) ? $category : ''] = true;
        }
        $result = array_keys($result);
        sort($result);

        return $result;
    }

    private function findProblem(LanguageInterface $language, ComplexArgument $argument): ?string
    {
        if (!in_array($argument->type, self::PLURAL_TYPES, true)) {
            return null;
        }

        $keywords = $this->getKeywords($argument);
        $expected = $this->pluralCategories[sprintf('%s %s', $language->getLocale(), $argument->type)]
            ??= $this->findPluralCategories($language, $argument->type);
        if ($keywords === $expected) {
            return null;
        }

        return sprintf(
            '{%s, %s}: categories "%s", but the language has "%s".',
            $argument->name,
            $argument->type,
            implode(' ', $keywords),
            implode(' ', $expected),
        );
    }

    /**
     * @return list<string> The selectors that aren't exact values (`=0`), sorted.
     */
    private function getKeywords(ComplexArgument $argument): array
    {
        $result = array_values(
            array_filter(
                $argument->selectors,
                static function (string $selector): bool {
                    return !str_starts_with($selector, '=');
                },
            ),
        );
        sort($result);

        return $result;
    }
}
