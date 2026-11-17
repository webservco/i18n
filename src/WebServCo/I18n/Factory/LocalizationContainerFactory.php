<?php

declare(strict_types=1);

namespace WebServCo\I18n\Factory;

use DateTimeZone;
use Override;
use Psr\Log\LoggerInterface;
use WebServCo\I18n\Container\LocalizationContainer;
use WebServCo\I18n\Contract\CatalogLoaderInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\LocalizationContainerFactoryInterface;
use WebServCo\I18n\Contract\LocalizationContainerInterface;
use WebServCo\I18n\Service\Formatter;
use WebServCo\I18n\Service\Translator;

/**
 * Creates the localization services of a language and a display time zone: the translator and the formatter.
 *
 * In a request, the language comes from `RequestLanguageReaderInterface`. A command or an email names its own.
 */
final class LocalizationContainerFactory implements LocalizationContainerFactoryInterface
{
    public function __construct(
        private CatalogLoaderInterface $catalogLoader,
        private LanguageInterface $defaultLanguage,
        private LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function createLocalizationContainer(
        LanguageInterface $language,
        DateTimeZone $timeZone,
    ): LocalizationContainerInterface {
        return new LocalizationContainer(
            new Formatter($language, $timeZone),
            new Translator($this->catalogLoader, $language, $this->defaultLanguage, $this->logger),
        );
    }
}
