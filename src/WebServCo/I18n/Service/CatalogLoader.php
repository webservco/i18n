<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service;

use InvalidArgumentException;
use Override;
use UnexpectedValueException;
use WebServCo\Configuration\Contract\ConfigurationLoaderInterface;
use WebServCo\I18n\Contract\CatalogLoaderInterface;
use WebServCo\I18n\Contract\LanguageInterface;

use function is_file;
use function is_string;
use function preg_match;
use function rtrim;
use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * Loads a translation catalog: `{directory}/{language code}/{catalog name}.php`, returning `name => message`.
 */
final class CatalogLoader implements CatalogLoaderInterface
{
    /**
     * Catalog names: lowercase words joined by underscores (eg. `layout`, `play_log`); the first part of a key.
     */
    public const string CATALOG_NAME_PATTERN = '/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/';

    private string $directoryPath;

    public function __construct(private ConfigurationLoaderInterface $configurationLoader, string $directoryPath)
    {
        // Make sure path contains trailing slash (trim + add back).
        $this->directoryPath = rtrim($directoryPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @return array<string,string> Message name => message; empty when the language has no such catalog file.
     */
    #[Override]
    public function load(LanguageInterface $language, string $catalogName): array
    {
        if (preg_match(self::CATALOG_NAME_PATTERN, $catalogName) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid catalog name: "%s".', $catalogName));
        }

        $filePath = sprintf(
            '%s%s%s%s.php',
            $this->directoryPath,
            $language->getCode(),
            DIRECTORY_SEPARATOR,
            $catalogName,
        );
        if (!is_file($filePath)) {
            return [];
        }

        return $this->loadFile($filePath);
    }

    /**
     * @return array<string,string>
     */
    private function loadFile(string $filePath): array
    {
        $result = [];

        foreach ($this->configurationLoader->loadFromFile($filePath) as $name => $message) {
            if (!is_string($name) || !is_string($message) || $message === '') {
                throw new UnexpectedValueException(sprintf('Invalid message in catalog "%s".', $filePath));
            }
            $result[$name] = $message;
        }

        return $result;
    }
}
