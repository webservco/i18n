<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\TestLanguage;
use Tests\Unit\Assets\ValueLanguage;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Factory\LanguageLinkViewFactory;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\View\LanguageLinkView;

use function array_map;

#[CoversClass(LanguageLinkView::class)]
#[CoversClass(LanguageLinkViewFactory::class)]
#[UsesClass(LanguageList::class)]
final class LanguageLinkViewFactoryTest extends TestCase
{
    public function testTheLanguageSwitchListsEveryLanguageWithTheCurrentOneMarked(): void
    {
        self::assertSame(
            [
                ['de', false, 'Deutsch', '?lang=de'],
                ['en', false, 'English', '?lang=en'],
                ['hu', true, 'Magyar', '?lang=hu'],
            ],
            $this->createLanguageLinks(TestLanguage::Hungarian),
        );
    }

    public function testTheCurrentLanguageIsFoundByItsCode(): void
    {
        self::assertSame(
            [
                ['de', true, 'Deutsch', '?lang=de'],
                ['en', false, 'English', '?lang=en'],
                ['hu', false, 'Magyar', '?lang=hu'],
            ],
            $this->createLanguageLinks(new ValueLanguage('de', 'de', 'Deutsch')),
        );
    }

    /**
     * @return list<array{0: string, 1: bool, 2: string, 3: string}> Code, current, name, URL.
     */
    private function createLanguageLinks(LanguageInterface $currentLanguage): array
    {
        $languageLinkViewFactory = new LanguageLinkViewFactory(
            new LanguageList(TestLanguage::cases(), TestLanguage::DEFAULT),
        );

        return array_map(
            static function (LanguageLinkView $link): array {
                return [$link->code, $link->isCurrent, $link->name, $link->url];
            },
            $languageLinkViewFactory->createLanguageLinks($currentLanguage),
        );
    }
}
