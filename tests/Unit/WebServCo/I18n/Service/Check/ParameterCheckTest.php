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
use WebServCo\I18n\Service\Check\ParameterCheck;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\Service\MessagePatternParser;

#[CoversClass(ParameterCheck::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(CatalogProblem::class)]
#[UsesClass(CatalogReader::class)]
#[UsesClass(ComplexArgument::class)]
#[UsesClass(LanguageList::class)]
#[UsesClass(MessagePattern::class)]
#[UsesClass(MessagePatternParser::class)]
final class ParameterCheckTest extends AbstractCatalogCheckTestCase
{
    public function testTheValidSetHasNoProblem(): void
    {
        self::assertSame([], new ParameterCheck($this->createCatalogReader('Valid'))->check());
    }

    /**
     * A lost argument, another type, other select cases. A key the default language lacks is not this check's.
     */
    public function testParametersThatDifferFromTheDefaultLanguageAreReported(): void
    {
        self::assertEquals(
            [
                $this->createProblem(
                    'page.greeting',
                    'Parameters {"arguments":[],"selectCases":[]}, not '
                    . '{"arguments":{"name":["none"]},"selectCases":[]} as in the default language.',
                ),
                $this->createProblem(
                    'page.records',
                    'Parameters {"arguments":{"count":["none"]},"selectCases":[]}, not '
                    . '{"arguments":{"count":["plural"]},"selectCases":[]} as in the default language.',
                ),
                $this->createProblem(
                    'page.shelf',
                    'Parameters {"arguments":{"gender":["select"]},"selectCases":{"gender":["female","other"]}}, '
                    . 'not {"arguments":{"gender":["select"]},"selectCases":{"gender":["female","male","other"]}} '
                    . 'as in the default language.',
                ),
            ],
            new ParameterCheck($this->createCatalogReader('Parameter'))->check(),
        );
    }

    private function createProblem(string $key, string $description): CatalogProblem
    {
        return new CatalogProblem(ParameterCheck::class, 'de', $key, $description);
    }
}
