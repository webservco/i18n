<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\LanguageResolverInterface;
use WebServCo\I18n\Enum\LanguageSource;

use function sprintf;

/**
 * Resolves the language of the request (`LanguageResolverInterface`) and gives it to the rest of the stack
 * as a request attribute (`LanguageInterface`; read it with `RequestLanguageReaderInterface`).
 *
 * Place it before the exception handlers, so that error and 404 pages have it too.
 *
 * Response:
 * - `Content-Language`;
 * - a language from `?lang=` is stored in the language cookie;
 * - otherwise the language came from a stored preference, the cookie or the `Accept-Language` header, which the
 *   response then varies on (a preference is read with the session cookie).
 */
final class LanguageMiddleware implements MiddlewareInterface
{
    // One year.
    public const int COOKIE_LIFETIME = 31_536_000;

    public const string REQUEST_ATTRIBUTE = 'language';

    public const string VARY = 'Cookie, Accept-Language';

    public function __construct(private LanguageResolverInterface $languageResolver, private bool $isCookieSecure)
    {
    }

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $languageResolution = $this->languageResolver->resolve($request);
        $language = $languageResolution->language;

        $response = $handler->handle($request->withAttribute(self::REQUEST_ATTRIBUTE, $language))
            ->withHeader('Content-Language', $language->getCode());

        if ($languageResolution->source === LanguageSource::Query) {
            return $response->withAddedHeader('Set-Cookie', $this->createCookie($language));
        }

        return $response->withAddedHeader('Vary', self::VARY);
    }

    /**
     * A preference cookie: the whole site, not readable by scripts, sent on top-level navigations from other sites.
     */
    private function createCookie(LanguageInterface $language): string
    {
        return sprintf(
            '%s=%s; Path=/; Max-Age=%d; SameSite=Lax; HttpOnly%s',
            LanguageResolver::COOKIE_NAME,
            $language->getCode(),
            self::COOKIE_LIFETIME,
            $this->isCookieSecure ? '; Secure' : '',
        );
    }
}
