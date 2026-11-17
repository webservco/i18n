<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\Check\StructureCheck;
use WebServCo\I18n\Service\LanguageList;

#[CoversClass(CatalogProblem::class)]
#[CoversClass(StructureCheck::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(CatalogReader::class)]
#[UsesClass(LanguageList::class)]
final class StructureCheckTest extends AbstractCatalogCheckTestCase
{
    public function testTheValidSetHasNoProblem(): void
    {
        self::assertSame([], new StructureCheck($this->createCatalogReader('Valid'))->check());
    }

    public function testDirectoriesCatalogsAndMessagesThatDifferAreReported(): void
    {
        self::assertEquals(
            [
                $this->createProblem('hu', null, 'No catalog directory.'),
                $this->createProblem(null, null, 'Directory "fr" that is not the code of a language of the list.'),
                $this->createProblem('de', null, 'Catalog "play_log" missing: the default language has it.'),
                $this->createProblem('de', null, 'Catalog "extra" not in the default language.'),
                $this->createProblem('de', 'page.title', 'Message missing: the default language has it.'),
                $this->createProblem('de', 'page.subtitle', 'Message not in the default language.'),
            ],
            new StructureCheck($this->createCatalogReader('Structure'))->check(),
        );
    }

    /**
     * A wrong catalog directory doesn't pass.
     */
    public function testNoDirectoryAtAllIsReported(): void
    {
        self::assertEquals(
            [
                $this->createProblem('de', null, 'No catalog directory.'),
                $this->createProblem('en', null, 'No catalog directory.'),
                $this->createProblem('hu', null, 'No catalog directory.'),
                $this->createProblem('ro', null, 'No catalog directory.'),
                $this->createProblem('en', null, 'The default language has no catalog.'),
            ],
            new StructureCheck($this->createCatalogReader('NoSuchDirectory'))->check(),
        );
    }

    private function createProblem(?string $languageCode, ?string $key, string $description): CatalogProblem
    {
        return new CatalogProblem(StructureCheck::class, $languageCode, $key, $description);
    }
}
