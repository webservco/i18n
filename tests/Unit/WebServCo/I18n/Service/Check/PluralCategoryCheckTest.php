<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\DataTransfer\ComplexArgument;
use WebServCo\I18n\DataTransfer\MessagePattern;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\Check\PluralCategoryCheck;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\Service\MessagePatternParser;

#[CoversClass(PluralCategoryCheck::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(CatalogProblem::class)]
#[UsesClass(CatalogReader::class)]
#[UsesClass(ComplexArgument::class)]
#[UsesClass(LanguageList::class)]
#[UsesClass(MessagePattern::class)]
#[UsesClass(MessagePatternParser::class)]
final class PluralCategoryCheckTest extends AbstractCatalogCheckTestCase
{
    /**
     * Romanian `few`, the ordinals of each language, exact values.
     */
    public function testTheValidSetHasNoProblem(): void
    {
        self::assertSame([], new PluralCategoryCheck($this->createCatalogReader('Valid'))->check());
    }

    public function testAMissingCategoryAndOneTheLanguageHasNotAreReported(): void
    {
        self::assertEquals(
            [
                $this->createProblem(
                    'en',
                    'page.place',
                    '{place, selectordinal}: categories "one other two", but the language has "few one other two".',
                ),
                $this->createProblem(
                    'en',
                    'page.records',
                    '{count, plural}: categories "one other zero", but the language has "one other".',
                ),
                $this->createProblem(
                    'ro',
                    'page.records',
                    '{count, plural}: categories "one other", but the language has "few one other".',
                ),
            ],
            new PluralCategoryCheck($this->createCatalogReader('PluralCategory'))->check(),
        );
    }

    private function createProblem(string $languageCode, string $key, string $description): CatalogProblem
    {
        return new CatalogProblem(PluralCategoryCheck::class, $languageCode, $key, $description);
    }
}
