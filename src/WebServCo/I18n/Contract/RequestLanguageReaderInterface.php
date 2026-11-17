<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The language `LanguageMiddleware` gave a request.
 */
interface RequestLanguageReaderInterface
{
    /**
     * @throws \UnexpectedValueException When the request has no language: the middleware did not process it.
     */
    public function getLanguage(ServerRequestInterface $request): LanguageInterface;
}
