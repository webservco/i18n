<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Unit\Assets\TestLanguage;
use UnexpectedValueException;
use WebServCo\I18n\Service\RequestLanguageReader;

#[CoversClass(RequestLanguageReader::class)]
final class RequestLanguageReaderTest extends TestCase
{
    public function testTheLanguageIsTheRequestAttributeOfTheMiddleware(): void
    {
        $requestLanguageReader = new RequestLanguageReader();

        self::assertSame(
            TestLanguage::German,
            $requestLanguageReader->getLanguage($this->createRequest(TestLanguage::German)),
        );
    }

    public function testARequestWithoutLanguageThrows(): void
    {
        $requestLanguageReader = new RequestLanguageReader();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageIs('The request has no language (LanguageMiddleware).');

        $requestLanguageReader->getLanguage($this->createRequest(null));
    }

    public function testAnAttributeThatIsNotALanguageThrows(): void
    {
        $requestLanguageReader = new RequestLanguageReader();

        $this->expectException(UnexpectedValueException::class);

        $requestLanguageReader->getLanguage($this->createRequest('de'));
    }

    private function createRequest(TestLanguage|string|null $language): ServerRequestInterface
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturnMap([['language', null, $language]]);

        return $request;
    }
}
