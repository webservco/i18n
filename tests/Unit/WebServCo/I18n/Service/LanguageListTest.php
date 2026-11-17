<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\TestLanguage;
use Tests\Unit\Assets\ValueLanguage;
use WebServCo\I18n\Service\LanguageList;

#[CoversClass(LanguageList::class)]
final class LanguageListTest extends TestCase
{
    public function testTheLanguagesKeepTheirOrder(): void
    {
        $languageList = new LanguageList(TestLanguage::cases(), TestLanguage::DEFAULT);

        self::assertSame(
            [TestLanguage::German, TestLanguage::English, TestLanguage::Hungarian],
            $languageList->getLanguages(),
        );
        self::assertSame(TestLanguage::English, $languageList->getDefaultLanguage());
    }

    public function testALanguageIsFoundByItsExactCode(): void
    {
        $languageList = new LanguageList(TestLanguage::cases(), TestLanguage::DEFAULT);

        self::assertSame(TestLanguage::German, $languageList->findByCode('de'));
        self::assertSame(TestLanguage::Hungarian, $languageList->findByCode('hu'));
        self::assertNull($languageList->findByCode('fr'));
        self::assertNull($languageList->findByCode('DE'));
        self::assertNull($languageList->findByCode(''));
    }

    public function testTheDefaultLanguageIsTheObjectOfTheList(): void
    {
        $languageList = new LanguageList(TestLanguage::cases(), new ValueLanguage('en', 'en_GB', 'English'));

        self::assertSame(TestLanguage::English, $languageList->getDefaultLanguage());
    }

    public function testALanguageThatIsNotAnEnumWorksToo(): void
    {
        $english = new ValueLanguage('en', 'en_GB', 'English');
        $languageList = new LanguageList([$english], $english);

        self::assertSame([$english], $languageList->getLanguages());
        self::assertSame($english, $languageList->findByCode('en'));
    }

    public function testADefaultLanguageNotInTheListIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The default language is not in the list: "en".');

        self::assertSame([], (new LanguageList([TestLanguage::German], TestLanguage::English))->getLanguages());
    }

    public function testAnEmptyListIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The default language is not in the list: "en".');

        self::assertSame([], (new LanguageList([], TestLanguage::English))->getLanguages());
    }

    public function testACodeGivenTwiceIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Language code given twice: "de".');

        $languages = [TestLanguage::German, TestLanguage::English, new ValueLanguage('de', 'de_AT', 'Deutsch')];

        self::assertSame([], (new LanguageList($languages, TestLanguage::English))->getLanguages());
    }

    #[DataProvider('invalidCodeProvider')]
    public function testACodeThatIsNotAPrimaryLanguageSubtagIsRefused(string $code): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Invalid language code: "' . $code . '".');

        $languages = [TestLanguage::English, new ValueLanguage($code, 'de', 'Deutsch')];

        self::assertSame([], (new LanguageList($languages, TestLanguage::English))->getLanguages());
    }

    /**
     * @return iterable<string,array{0: string}>
     */
    public static function invalidCodeProvider(): iterable
    {
        yield 'region' => ['de-AT'];
        yield 'uppercase' => ['DE'];
        yield 'empty' => [''];
        yield 'too long' => ['deut'];
        yield 'path' => ['..'];
    }
}
