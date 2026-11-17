<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service;

use InvalidArgumentException;
use MessageFormatter;
use OutOfBoundsException;
use Override;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;
use WebServCo\I18n\Contract\CatalogLoaderInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\TranslatorInterface;

use function sprintf;
use function strpos;
use function substr;

/**
 * Translates message keys (`{catalog}.{name}`) into one language, with ICU MessageFormat.
 *
 * A key missing in the language falls back to the default language and is logged.
 * A key missing in the default language is a programming error: it throws.
 */
final class Translator implements TranslatorInterface
{
    /**
     * Loaded catalogs: language code => catalog name => message name => message.
     *
     * @var array<string,array<string,array<string,string>>>
     */
    private array $catalogs = [];

    public function __construct(
        private CatalogLoaderInterface $catalogLoader,
        private LanguageInterface $language,
        private LanguageInterface $defaultLanguage,
        private LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function getLanguage(): LanguageInterface
    {
        return $this->language;
    }

    /**
     * @param array<string,float|int|string> $parameters
     */
    #[Override]
    public function translate(string $key, array $parameters = []): string
    {
        [$catalogName, $name] = $this->splitKey($key);

        foreach ($this->getLanguages() as $language) {
            $message = $this->getCatalog($language, $catalogName)[$name] ?? null;
            if ($message !== null) {
                // The plural and number rules of the message's own language.
                return $this->format($language, $key, $message, $parameters);
            }
            if ($this->isDefaultLanguage($language)) {
                continue;
            }

            $this->logger->warning(
                'Translation missing, using the default language.',
                ['key' => $key, 'language' => $language->getCode()],
            );
        }

        throw new OutOfBoundsException(sprintf('Translation key not found: "%s".', $key));
    }

    /**
     * @param array<string,float|int|string> $parameters
     */
    private function format(LanguageInterface $language, string $key, string $message, array $parameters): string
    {
        $messageFormatter = new MessageFormatter($language->getLocale(), $message);
        $result = $messageFormatter->format($parameters);
        if ($result === false) {
            throw new UnexpectedValueException(sprintf('Error formatting the message of "%s".', $key));
        }

        return $result;
    }

    /**
     * @return array<string,string>
     */
    private function getCatalog(LanguageInterface $language, string $catalogName): array
    {
        return $this->catalogs[$language->getCode()][$catalogName]
            ??= $this->catalogLoader->load($language, $catalogName);
    }

    /**
     * The language of the translator, then the default language.
     *
     * @return non-empty-list<\WebServCo\I18n\Contract\LanguageInterface>
     */
    private function getLanguages(): array
    {
        if ($this->isDefaultLanguage($this->language)) {
            return [$this->defaultLanguage];
        }

        return [$this->language, $this->defaultLanguage];
    }

    private function isDefaultLanguage(LanguageInterface $language): bool
    {
        return $language->getCode() === $this->defaultLanguage->getCode();
    }

    /**
     * @return array{0: string, 1: string} Catalog name, message name.
     */
    private function splitKey(string $key): array
    {
        $position = strpos($key, '.');
        if ($position === false) {
            throw new InvalidArgumentException(sprintf('Invalid translation key: "%s".', $key));
        }

        return [substr($key, 0, $position), substr($key, $position + 1)];
    }
}
