<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Unit\Assets\TestLanguage;
use Tests\Unit\Assets\ValueLanguage;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\PreferredLanguageProviderInterface;
use WebServCo\I18n\DataTransfer\LanguageResolution;
use WebServCo\I18n\Enum\LanguageSource;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\Service\LanguageResolver;

#[CoversClass(LanguageResolution::class)]
#[CoversClass(LanguageResolver::class)]
#[UsesClass(LanguageList::class)]
final class LanguageResolverTest extends TestCase
{
    public function testTheQueryComesFirst(): void
    {
        $this->assertResolution(
            TestLanguage::Hungarian,
            LanguageSource::Query,
            $this->createRequest(['lang' => 'hu'], ['lang' => 'de'], 'de'),
            $this->createProvider(TestLanguage::German),
        );
    }

    public function testThePreferenceComesBeforeTheCookie(): void
    {
        $this->assertResolution(
            TestLanguage::Hungarian,
            LanguageSource::Preference,
            $this->createRequest([], ['lang' => 'de'], 'de'),
            $this->createProvider(TestLanguage::Hungarian),
        );
    }

    public function testThePreferenceIsTheLanguageOfTheList(): void
    {
        // Compared by code: the result is the list's object, so project code can check its own class.
        $this->assertResolution(
            TestLanguage::German,
            LanguageSource::Preference,
            $this->createRequest([], [], ''),
            $this->createProvider(new ValueLanguage('de', 'de', 'Deutsch')),
        );
    }

    public function testWithoutPreferenceTheCookieDecides(): void
    {
        $this->assertResolution(
            TestLanguage::German,
            LanguageSource::Cookie,
            $this->createRequest([], ['lang' => 'de'], 'hu'),
            $this->createProvider(null),
        );
    }

    public function testAnUnknownPreferenceIsIgnored(): void
    {
        $this->assertResolution(
            TestLanguage::German,
            LanguageSource::Cookie,
            $this->createRequest([], ['lang' => 'de'], 'hu'),
            $this->createProvider(new ValueLanguage('fr', 'fr', 'Français')),
        );
    }

    public function testTheCookieComesBeforeTheHeader(): void
    {
        $this->assertResolution(
            TestLanguage::German,
            LanguageSource::Cookie,
            $this->createRequest([], ['lang' => 'de'], 'hu'),
        );
    }

    public function testTheHeaderComesBeforeTheDefault(): void
    {
        $this->assertResolution(
            TestLanguage::Hungarian,
            LanguageSource::AcceptLanguage,
            $this->createRequest([], [], 'hu'),
        );
    }

    public function testWithoutAnyTheDefaultLanguage(): void
    {
        $this->assertResolution(TestLanguage::English, LanguageSource::Fallback, $this->createRequest([], [], ''));
    }

    public function testUnknownValuesAreIgnored(): void
    {
        $this->assertResolution(
            TestLanguage::German,
            LanguageSource::AcceptLanguage,
            $this->createRequest(['lang' => 'fr'], ['lang' => 'DE'], 'de'),
        );
        $this->assertResolution(
            TestLanguage::English,
            LanguageSource::Fallback,
            $this->createRequest(['lang' => ['hu']], ['lang' => ''], 'fr, *'),
        );
    }

    public function testTheHeaderLanguageWithTheHighestQualityWins(): void
    {
        $this->assertHeaderLanguage(TestLanguage::Hungarian, 'fr-FR, de;q=0.5, hu-HU;q=0.8, en;q=0.7');
        $this->assertHeaderLanguage(TestLanguage::German, 'de-CH, en');
        $this->assertHeaderLanguage(TestLanguage::German, 'de-AT;q=0.9, en;Q=0.9');
        $this->assertHeaderLanguage(TestLanguage::Hungarian, 'hu-HU, hu;q=0.9, en;q=0.8');
        $this->assertHeaderLanguage(TestLanguage::English, 'de;q=0, EN-gb;q=0.1');
    }

    public function testAnInvalidQualitySelectsNothing(): void
    {
        $this->assertHeaderLanguage(TestLanguage::English, 'de;q=high');
        $this->assertHeaderLanguage(TestLanguage::English, 'de;q=2, *');
    }

    private function assertHeaderLanguage(TestLanguage $expected, string $acceptLanguage): void
    {
        self::assertSame(
            $expected,
            $this->createLanguageResolver()->resolve($this->createRequest([], [], $acceptLanguage))->language,
            $acceptLanguage,
        );
    }

    private function assertResolution(
        TestLanguage $expectedLanguage,
        LanguageSource $expectedSource,
        ServerRequestInterface $request,
        ?PreferredLanguageProviderInterface $preferredLanguageProvider = null,
    ): void {
        $languageResolution = $this->createLanguageResolver($preferredLanguageProvider)->resolve($request);

        self::assertSame($expectedLanguage, $languageResolution->language);
        self::assertSame($expectedSource, $languageResolution->source);
    }

    private function createLanguageResolver(
        ?PreferredLanguageProviderInterface $preferredLanguageProvider = null,
    ): LanguageResolver {
        return new LanguageResolver(
            new LanguageList(TestLanguage::cases(), TestLanguage::DEFAULT),
            $preferredLanguageProvider,
        );
    }

    private function createProvider(?LanguageInterface $preferredLanguage): PreferredLanguageProviderInterface
    {
        $preferredLanguageProvider = self::createStub(PreferredLanguageProviderInterface::class);
        $preferredLanguageProvider->method('getPreferredLanguage')->willReturn($preferredLanguage);

        return $preferredLanguageProvider;
    }

    /**
     * A stub: the request implementations of webservco/http need a web server (`apache_request_headers()`).
     *
     * @param array<string,string|list<string>> $queryParams
     * @param array<string,string> $cookieParams
     */
    private function createRequest(
        array $queryParams,
        array $cookieParams,
        string $acceptLanguage,
    ): ServerRequestInterface {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);
        $request->method('getCookieParams')->willReturn($cookieParams);
        $request->method('getHeaderLine')->willReturnMap([['Accept-Language', $acceptLanguage]]);

        return $request;
    }
}
