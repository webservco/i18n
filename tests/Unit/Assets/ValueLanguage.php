<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use Override;
use WebServCo\I18n\Contract\LanguageInterface;

/**
 * A language that is not an enum: two objects with the same code are the same language.
 */
final readonly class ValueLanguage implements LanguageInterface
{
    public function __construct(private string $code, private string $locale, private string $nativeName)
    {
    }

    #[Override]
    public function getCode(): string
    {
        return $this->code;
    }

    #[Override]
    public function getLocale(): string
    {
        return $this->locale;
    }

    #[Override]
    public function getNativeName(): string
    {
        return $this->nativeName;
    }
}
