<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Assets\TestLanguage;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\DataTransfer\ComplexArgument;
use WebServCo\I18n\DataTransfer\MessagePattern;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\Service\MessagePatternParser;

use function array_map;

#[CoversClass(CatalogReader::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(ComplexArgument::class)]
#[UsesClass(LanguageList::class)]
#[UsesClass(MessagePattern::class)]
#[UsesClass(MessagePatternParser::class)]
final class CatalogReaderTest extends AbstractCatalogCheckTestCase
{
    public function testTheDirectoriesAreListedSorted(): void
    {
        self::assertSame(['de', 'en', 'fr', 'ro'], $this->createCatalogReader('Structure')->getDirectoryNames());
        self::assertSame([], $this->createCatalogReader('NoSuchDirectory')->getDirectoryNames());
    }

    public function testTheCatalogsAreListedSortedWithInvalidNames(): void
    {
        $catalogReader = $this->createCatalogReader('MessageSyntax');

        self::assertSame(['Bad-Name', 'page'], $catalogReader->getCatalogNames(TestLanguage::English));
        self::assertSame([], $catalogReader->getCatalogNames(TestLanguage::Hungarian));
    }

    public function testTheMessagesOfTheCatalogsWithAValidNameAreLoaded(): void
    {
        $catalogReader = $this->createCatalogReader('MessageSyntax');

        self::assertSame(
            [
                'page.count' => '{count, number} plays',
                'page.icu_error' => 'Hello, {name',
                'page.offset' => '{count, plural, offset:1other {#}}',
                'page.padded' => ' Records ',
                'page.Title' => 'Records',
                'page.title' => 'Records',
            ],
            $catalogReader->getMessages(TestLanguage::English),
        );
        self::assertSame([], $catalogReader->getMessages(TestLanguage::Hungarian));
    }

    public function testTheMessagesTheParserCanNotReadAreLeftOut(): void
    {
        self::assertEquals(
            [
                'page.count' => new MessagePattern(['count' => ['number']], [], '  plays'),
                'page.padded' => new MessagePattern([], [], ' Records '),
                'page.Title' => new MessagePattern([], [], 'Records'),
                'page.title' => new MessagePattern([], [], 'Records'),
            ],
            $this->createCatalogReader('MessageSyntax')->getMessagePatterns(TestLanguage::English),
        );
    }

    public function testTheLanguageListIsTheOneGiven(): void
    {
        $languageList = $this->createCatalogReader('Valid')->getLanguageList();

        self::assertSame(TestLanguage::English, $languageList->getDefaultLanguage());
        self::assertSame(
            ['de', 'en', 'hu', 'ro'],
            array_map(
                static function (LanguageInterface $language): string {
                    return $language->getCode();
                },
                $languageList->getLanguages(),
            ),
        );
    }
}
