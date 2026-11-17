<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use Override;
use WebServCo\I18n\Contract\LanguageInterface;

/**
 * The languages of the tests, as a project describes its own: English is the default, Hungarian has no catalogs.
 *
 * Cases are in the order of the language switch: alphabetical by native name.
 */
enum TestLanguage: string implements LanguageInterface
{
    case German = 'de';
    case English = 'en';
    case Hungarian = 'hu';

    public const TestLanguage DEFAULT = self::English;

    #[Override]
    public function getCode(): string
    {
        return $this->value;
    }

    /**
     * English formats are British: European formats in every language.
     */
    #[Override]
    public function getLocale(): string
    {
        return match ($this) {
            self::English => 'en_GB',
            default => $this->value,
        };
    }

    #[Override]
    public function getNativeName(): string
    {
        return match ($this) {
            self::German => 'Deutsch',
            self::English => 'English',
            self::Hungarian => 'Magyar',
        };
    }
}
