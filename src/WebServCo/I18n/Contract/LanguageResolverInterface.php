<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

use Psr\Http\Message\ServerRequestInterface;
use WebServCo\I18n\DataTransfer\LanguageResolution;

/**
 * The language of a request, and where it comes from.
 */
interface LanguageResolverInterface
{
    public function resolve(ServerRequestInterface $request): LanguageResolution;
}
