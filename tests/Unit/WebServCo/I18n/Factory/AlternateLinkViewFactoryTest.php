<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\TestLanguage;
use WebServCo\I18n\Factory\AlternateLinkViewFactory;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\View\AlternateLinkView;

use function array_map;

#[CoversClass(AlternateLinkView::class)]
#[CoversClass(AlternateLinkViewFactory::class)]
#[UsesClass(LanguageList::class)]
final class AlternateLinkViewFactoryTest extends TestCase
{
    public function testEveryLanguageVersionAndTheDefaultAreListed(): void
    {
        self::assertSame(
            [
                ['de', 'https://example.test/info/about?lang=de'],
                ['en', 'https://example.test/info/about?lang=en'],
                ['hu', 'https://example.test/info/about?lang=hu'],
                ['x-default', 'https://example.test/info/about'],
            ],
            $this->createAlternateLinks('https://example.test/info/about'),
        );
    }

    public function testThePortAndTheBasePathStay(): void
    {
        self::assertSame(
            [
                ['de', 'http://example.test:8080/v3/info/about?lang=de'],
                ['en', 'http://example.test:8080/v3/info/about?lang=en'],
                ['hu', 'http://example.test:8080/v3/info/about?lang=hu'],
                ['x-default', 'http://example.test:8080/v3/info/about'],
            ],
            $this->createAlternateLinks('http://example.test:8080/v3/info/about'),
        );
    }

    public function testTheHomePage(): void
    {
        self::assertSame(
            [
                ['de', 'https://example.test/?lang=de'],
                ['en', 'https://example.test/?lang=en'],
                ['hu', 'https://example.test/?lang=hu'],
                ['x-default', 'https://example.test/'],
            ],
            $this->createAlternateLinks('https://example.test/'),
        );
    }

    public function testTheQueryAndTheFragmentAreDropped(): void
    {
        self::assertSame(
            [
                ['de', 'https://example.test/info?lang=de'],
                ['en', 'https://example.test/info?lang=en'],
                ['hu', 'https://example.test/info?lang=hu'],
                ['x-default', 'https://example.test/info'],
            ],
            $this->createAlternateLinks('https://example.test/info?lang=hu&page=2#top'),
        );
        self::assertSame(
            $this->createAlternateLinks('https://example.test/info'),
            $this->createAlternateLinks('https://example.test/info#top'),
        );
    }

    /**
     * @return list<array{0: string, 1: string}> Hreflang, URL.
     */
    private function createAlternateLinks(string $pageUrl): array
    {
        $alternateLinkViewFactory = new AlternateLinkViewFactory(
            new LanguageList(TestLanguage::cases(), TestLanguage::DEFAULT),
        );

        return array_map(
            static function (AlternateLinkView $alternateLinkView): array {
                return [$alternateLinkView->hreflang, $alternateLinkView->url];
            },
            $alternateLinkViewFactory->createAlternateLinks($pageUrl),
        );
    }
}
