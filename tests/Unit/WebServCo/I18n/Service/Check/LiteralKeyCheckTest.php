<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use UnexpectedValueException;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\Check\LiteralKeyCheck;
use WebServCo\I18n\Service\LanguageList;

#[CoversClass(LiteralKeyCheck::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(CatalogProblem::class)]
#[UsesClass(CatalogReader::class)]
#[UsesClass(LanguageList::class)]
final class LiteralKeyCheckTest extends AbstractCatalogCheckTestCase
{
    /**
     * Strings that aren't keys, keys in comments and in inline HTML are not checked.
     */
    public function testTheKeysOfTheValidSourceExist(): void
    {
        self::assertSame([], $this->createCheck(['known'])->check());
    }

    /**
     * Once per key, with the first file that has it.
     */
    public function testAKeyTheDefaultLanguageLacksIsReported(): void
    {
        self::assertEquals(
            [
                $this->createProblem('page.no_such_key', 'Not in the default language (unknown/Keys.php).'),
                $this->createProblem('play_log.typo', 'Not in the default language (unknown/Keys.php).'),
            ],
            $this->createCheck(['known', 'unknown'])->check(),
        );
    }

    /**
     * Wrong source directories don't pass.
     */
    public function testNoKeyAtAllIsReported(): void
    {
        self::assertEquals(
            [new CatalogProblem(LiteralKeyCheck::class, null, null, 'No literal key in the source directories.')],
            $this->createCheck(['nokeys'])->check(),
        );
    }

    public function testASourceDirectoryThatDoesNotExistThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);

        self::assertSame([], $this->createCheck(['nosuchdirectory'])->check());
    }

    /**
     * @param list<string> $sourceDirectories
     */
    private function createCheck(array $sourceDirectories): LiteralKeyCheck
    {
        return new LiteralKeyCheck(
            $this->createCatalogReader('Valid'),
            $this->getFixturePath('Source'),
            $sourceDirectories,
        );
    }

    private function createProblem(string $key, string $description): CatalogProblem
    {
        return new CatalogProblem(LiteralKeyCheck::class, 'en', $key, $description);
    }
}
