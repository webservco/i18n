<?php

declare(strict_types=1);

namespace WebServCo\I18n\Container;

use Override;
use WebServCo\I18n\Contract\FormatterInterface;
use WebServCo\I18n\Contract\LocalizationContainerInterface;
use WebServCo\I18n\Contract\TranslatorInterface;

/**
 * The translator and the formatter of one language and time zone (`LocalizationContainerFactory`).
 */
final class LocalizationContainer implements LocalizationContainerInterface
{
    public function __construct(private FormatterInterface $formatter, private TranslatorInterface $translator)
    {
    }

    #[Override]
    public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    #[Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
