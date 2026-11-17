<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Unit\Assets\TestLanguage;
use WebServCo\Http\Factory\Message\Response\ResponseFactory;
use WebServCo\Http\Factory\Message\Stream\StreamFactory;
use WebServCo\Http\Service\Message\Response\StatusCodeService;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\PreferredLanguageProviderInterface;
use WebServCo\I18n\DataTransfer\LanguageResolution;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\Service\LanguageMiddleware;
use WebServCo\I18n\Service\LanguageResolver;

#[CoversClass(LanguageMiddleware::class)]
#[UsesClass(LanguageList::class)]
#[UsesClass(LanguageResolution::class)]
#[UsesClass(LanguageResolver::class)]
final class LanguageMiddlewareTest extends TestCase
{
    /**
     * Request attributes the middleware set, by name.
     *
     * @var array<string,\WebServCo\I18n\Contract\LanguageInterface>
     */
    private array $requestAttributes = [];

    private ?ServerRequestInterface $handledRequest = null;

    public function testTheHandlerGetsTheLanguageAsARequestAttribute(): void
    {
        $this->process(['lang' => 'hu'], [], true);

        self::assertSame(['language' => TestLanguage::Hungarian], $this->requestAttributes);
        self::assertNotNull($this->handledRequest);
    }

    public function testALanguageFromTheQueryIsStoredInTheCookie(): void
    {
        $response = $this->process(['lang' => 'de'], ['lang' => 'hu'], true);

        self::assertSame('de', $response->getHeaderLine('Content-Language'));
        self::assertSame(
            'lang=de; Path=/; Max-Age=31536000; SameSite=Lax; HttpOnly; Secure',
            $response->getHeaderLine('Set-Cookie'),
        );
        self::assertSame('HX-Request', $response->getHeaderLine('Vary'));
    }

    public function testTheCookieIsSecureOnlyWhenEnabled(): void
    {
        self::assertSame(
            'lang=hu; Path=/; Max-Age=31536000; SameSite=Lax; HttpOnly',
            $this->process(['lang' => 'hu'], [], false)->getHeaderLine('Set-Cookie'),
        );
    }

    public function testAnyOtherLanguageVariesOnTheCookieAndTheHeader(): void
    {
        $response = $this->process([], ['lang' => 'hu'], true);

        self::assertSame('hu', $response->getHeaderLine('Content-Language'));
        self::assertFalse($response->hasHeader('Set-Cookie'));
        self::assertSame('HX-Request, Cookie, Accept-Language', $response->getHeaderLine('Vary'));

        $response = $this->process([], [], true);

        self::assertSame('en', $response->getHeaderLine('Content-Language'));
        self::assertSame('HX-Request, Cookie, Accept-Language', $response->getHeaderLine('Vary'));
    }

    public function testAPreferenceSetsNoCookieAndVariesOnTheCookie(): void
    {
        // The preference is read with the session cookie: the response depends on the cookies.
        $response = $this->process([], ['lang' => 'hu'], true, TestLanguage::German);

        self::assertSame(['language' => TestLanguage::German], $this->requestAttributes);
        self::assertSame('de', $response->getHeaderLine('Content-Language'));
        self::assertFalse($response->hasHeader('Set-Cookie'));
        self::assertSame('HX-Request, Cookie, Accept-Language', $response->getHeaderLine('Vary'));
    }

    /**
     * @param array<string,string> $queryParams
     * @param array<string,string> $cookieParams
     */
    private function process(
        array $queryParams,
        array $cookieParams,
        bool $isCookieSecure,
        ?LanguageInterface $preferredLanguage = null,
    ): ResponseInterface {
        $responseFactory = new ResponseFactory(new StatusCodeService(), new StreamFactory());
        $handler = self::createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            function (ServerRequestInterface $request) use ($responseFactory): ResponseInterface {
                $this->handledRequest = $request;

                return $responseFactory->createResponse(200)->withHeader('Vary', 'HX-Request');
            },
        );
        $preferredLanguageProvider = self::createStub(PreferredLanguageProviderInterface::class);
        $preferredLanguageProvider->method('getPreferredLanguage')->willReturn($preferredLanguage);
        $middleware = new LanguageMiddleware(
            new LanguageResolver(
                new LanguageList(TestLanguage::cases(), TestLanguage::DEFAULT),
                $preferredLanguageProvider,
            ),
            $isCookieSecure,
        );

        return $middleware->process($this->createRequest($queryParams, $cookieParams), $handler);
    }

    /**
     * A stub, which records the attributes set on it: the request implementations of webservco/http need a web server
     * (`apache_request_headers()`).
     *
     * @param array<string,string> $queryParams
     * @param array<string,string> $cookieParams
     */
    private function createRequest(array $queryParams, array $cookieParams): ServerRequestInterface
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);
        $request->method('getCookieParams')->willReturn($cookieParams);
        $request->method('getHeaderLine')->willReturn('');
        $request->method('withAttribute')->willReturnCallback(
            function (string $name, LanguageInterface $value) use ($request): ServerRequestInterface {
                $this->requestAttributes[$name] = $value;

                return $request;
            },
        );

        return $request;
    }
}
