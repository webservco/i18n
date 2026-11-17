<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service;

use Override;
use Psr\Http\Message\ServerRequestInterface;
use UnexpectedValueException;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\RequestLanguageReaderInterface;

/**
 * Reads the language `LanguageMiddleware` gave the request (its request attribute).
 */
final class RequestLanguageReader implements RequestLanguageReaderInterface
{
    #[Override]
    public function getLanguage(ServerRequestInterface $request): LanguageInterface
    {
        $language = $request->getAttribute(LanguageMiddleware::REQUEST_ATTRIBUTE);
        if (!$language instanceof LanguageInterface) {
            throw new UnexpectedValueException('The request has no language (LanguageMiddleware).');
        }

        return $language;
    }
}
