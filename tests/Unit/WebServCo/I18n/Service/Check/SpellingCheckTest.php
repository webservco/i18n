<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Assets\TestLanguage;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\DataTransfer\ComplexArgument;
use WebServCo\I18n\DataTransfer\MessagePattern;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\Check\SpellingCheck;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\Service\MessagePatternParser;

use function array_map;

#[CoversClass(SpellingCheck::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(CatalogProblem::class)]
#[UsesClass(CatalogReader::class)]
#[UsesClass(ComplexArgument::class)]
#[UsesClass(LanguageList::class)]
#[UsesClass(MessagePattern::class)]
#[UsesClass(MessagePatternParser::class)]
final class SpellingCheckTest extends AbstractCatalogCheckTestCase
{
    /**
     * British spellings, and words that only look like US ones: size, prize, seized, citizen, Colorado, ...
     */
    public function testTheValidSetHasNoProblem(): void
    {
        self::assertSame(
            [],
            new SpellingCheck($this->createCatalogReader('Valid'), TestLanguage::English)->check(),
        );
    }

    /**
     * In the text of the messages, cases included; not in argument names, and not in other languages.
     */
    public function testTheUsSpellingsOfTheLanguageAreReported(): void
    {
        self::assertEquals(
            [
                ...$this->createProblems('en', 'page.plural', ['color', 'colors']),
                ...$this->createProblems(
                    'en',
                    'page.us',
                    [
                        'synchronize', 'Organization', 'analyzing', 'color', 'Favorite', 'behavioral', 'centers',
                        'catalog', 'dialogs', 'canceled', 'labeling', 'gray',
                    ],
                ),
            ],
            new SpellingCheck($this->createCatalogReader('Spelling'), TestLanguage::English)->check(),
        );
    }

    public function testOtherPatternsReplaceTheUsSpellings(): void
    {
        self::assertEquals(
            $this->createProblems('de', 'page.us', ['color']),
            new SpellingCheck($this->createCatalogReader('Spelling'), TestLanguage::German, ['/\bcolor\b/u'])->check(),
        );
    }

    /**
     * @param list<string> $words
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function createProblems(string $languageCode, string $key, array $words): array
    {
        return array_map(
            static function (string $word) use ($languageCode, $key): CatalogProblem {
                return new CatalogProblem(
                    SpellingCheck::class,
                    $languageCode,
                    $key,
                    'Refused spelling: "' . $word . '".',
                );
            },
            $words,
        );
    }
}
