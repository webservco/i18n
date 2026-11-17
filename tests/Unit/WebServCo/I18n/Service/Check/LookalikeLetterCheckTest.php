<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\Check\LookalikeLetterCheck;
use WebServCo\I18n\Service\LanguageList;

#[CoversClass(LookalikeLetterCheck::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(CatalogProblem::class)]
#[UsesClass(CatalogReader::class)]
#[UsesClass(LanguageList::class)]
final class LookalikeLetterCheckTest extends AbstractCatalogCheckTestCase
{
    public function testTheValidSetHasNoProblem(): void
    {
        self::assertSame([], new LookalikeLetterCheck($this->createCatalogReader('Valid'))->check());
    }

    public function testTheHungarianAndRomanianLookalikesAreReported(): void
    {
        self::assertEquals(
            [
                $this->createProblem('hu', 'page.title', '"õ" instead of "ő".'),
                $this->createProblem('hu', 'page.title', '"û" instead of "ű".'),
                $this->createProblem('ro', 'page.title', '"ã" instead of "ă".'),
                $this->createProblem('ro', 'page.title', '"ş" instead of "ș".'),
                $this->createProblem('ro', 'page.title', '"ţ" instead of "ț".'),
                $this->createProblem('ro', 'page.upper', '"Ã" instead of "Ă".'),
                $this->createProblem('ro', 'page.upper', '"Ş" instead of "Ș".'),
                $this->createProblem('ro', 'page.upper', '"Ţ" instead of "Ț".'),
            ],
            new LookalikeLetterCheck($this->createCatalogReader('LookalikeLetter'))->check(),
        );
    }

    public function testOtherLettersReplaceTheDefaultOnes(): void
    {
        self::assertEquals(
            [$this->createProblem('en', 'page.title', '"Ş" instead of "S".')],
            new LookalikeLetterCheck($this->createCatalogReader('LookalikeLetter'), ['en' => ['Ş' => 'S']])->check(),
        );
    }

    private function createProblem(?string $languageCode, ?string $key, string $description): CatalogProblem
    {
        return new CatalogProblem(LookalikeLetterCheck::class, $languageCode, $key, $description);
    }
}
