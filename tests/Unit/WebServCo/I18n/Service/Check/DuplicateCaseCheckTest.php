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
use WebServCo\I18n\Service\Check\DuplicateCaseCheck;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\Service\MessagePatternParser;

#[CoversClass(DuplicateCaseCheck::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(CatalogProblem::class)]
#[UsesClass(CatalogReader::class)]
#[UsesClass(ComplexArgument::class)]
#[UsesClass(LanguageList::class)]
#[UsesClass(MessagePattern::class)]
#[UsesClass(MessagePatternParser::class)]
final class DuplicateCaseCheckTest extends AbstractCatalogCheckTestCase
{
    public function testTheValidSetHasNoProblem(): void
    {
        self::assertSame([], new DuplicateCaseCheck($this->createCatalogReader('Valid'))->check());
    }

    /**
     * Once per case, however often it comes.
     */
    public function testACaseGivenTwiceIsReported(): void
    {
        self::assertEquals(
            [
                $this->createProblem(
                    'de',
                    'page.shelf',
                    '{gender, select}: case "male" more than once (ICU uses the first).',
                ),
                $this->createProblem(
                    'en',
                    'page.records',
                    '{count, plural}: case "one" more than once (ICU uses the first).',
                ),
                $this->createProblem(
                    'en',
                    'page.records',
                    '{count, plural}: case "other" more than once (ICU uses the first).',
                ),
            ],
            new DuplicateCaseCheck($this->createCatalogReader('DuplicateCase'))->check(),
        );
    }

    private function createProblem(string $languageCode, string $key, string $description): CatalogProblem
    {
        return new CatalogProblem(DuplicateCaseCheck::class, $languageCode, $key, $description);
    }
}
