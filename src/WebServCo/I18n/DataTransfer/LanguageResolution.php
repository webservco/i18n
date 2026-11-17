<?php

declare(strict_types=1);

namespace WebServCo\I18n\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Enum\LanguageSource;

/**
 * The language of a request, and where it comes from (`LanguageResolver`).
 */
final readonly class LanguageResolution implements DataTransferInterface
{
    public function __construct(public LanguageInterface $language, public LanguageSource $source)
    {
    }
}
