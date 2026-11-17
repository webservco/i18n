<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use InvalidArgumentException;
use WebServCo\I18n\Contract\CatalogLoaderInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\LanguageListInterface;
use WebServCo\I18n\DataTransfer\MessagePattern;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\MessagePatternParser;

use function array_map;
use function basename;
use function glob;
use function preg_match;
use function rtrim;
use function sort;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const GLOB_ONLYDIR;

/**
 * Reads the catalogs of a project for the catalog checks: one reader for all of them, as it keeps what it read.
 *
 * Catalog files are listed with their names as they are; only the catalogs with a valid name are loaded
 * (`MessageSyntaxCheck` reports the others).
 */
final class CatalogReader
{
    private string $directoryPath;

    /**
     * Language code => key => the structure of its message.
     *
     * @var array<string,array<string,\WebServCo\I18n\DataTransfer\MessagePattern>>
     */
    private array $messagePatterns = [];

    private MessagePatternParser $messagePatternParser;

    /**
     * Language code => key => message.
     *
     * @var array<string,array<string,string>>
     */
    private array $messages = [];

    public function __construct(
        private CatalogLoaderInterface $catalogLoader,
        private LanguageListInterface $languageList,
        string $directoryPath,
    ) {
        $this->directoryPath = rtrim($directoryPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->messagePatternParser = new MessagePatternParser();
    }

    /**
     * The file names of the language's catalogs, without `.php`, valid or not.
     *
     * @return list<string> Sorted.
     */
    public function getCatalogNames(LanguageInterface $language): array
    {
        return $this->getBaseNames(glob(sprintf('%s%s/*.php', $this->directoryPath, $language->getCode())), '.php');
    }

    /**
     * @return list<string> The names of the directories in the catalog directory, sorted.
     */
    public function getDirectoryNames(): array
    {
        return $this->getBaseNames(glob(sprintf('%s*', $this->directoryPath), GLOB_ONLYDIR), '');
    }

    public function getLanguageList(): LanguageListInterface
    {
        return $this->languageList;
    }

    /**
     * The structure of every message of the language that the parser can read (`MessageSyntaxCheck` reports the
     * others).
     *
     * @return array<string,\WebServCo\I18n\DataTransfer\MessagePattern> Key => the structure of its message.
     */
    public function getMessagePatterns(LanguageInterface $language): array
    {
        return $this->messagePatterns[$language->getCode()] ??= $this->parseMessages($language);
    }

    /**
     * Every message of the language, in its catalogs with a valid name.
     *
     * @return array<string,string> Key (`{catalog}.{name}`) => message.
     */
    public function getMessages(LanguageInterface $language): array
    {
        return $this->messages[$language->getCode()] ??= $this->loadMessages($language);
    }

    /**
     * @param array<string>|false $paths What `glob()` found.
     * @return list<string> Sorted.
     */
    private function getBaseNames(array|false $paths, string $suffix): array
    {
        $result = array_map(
            static function (string $path) use ($suffix): string {
                return basename($path, $suffix);
            },
            // An error lists nothing: `StructureCheck` then reports missing directories or catalogs.
            $paths === false ? [] : $paths,
        );
        sort($result);

        return $result;
    }

    /**
     * @return array<string,string>
     */
    private function loadMessages(LanguageInterface $language): array
    {
        $result = [];

        foreach ($this->getCatalogNames($language) as $catalogName) {
            if (preg_match(CatalogLoader::CATALOG_NAME_PATTERN, $catalogName) !== 1) {
                continue;
            }

            foreach ($this->catalogLoader->load($language, $catalogName) as $name => $message) {
                $result[sprintf('%s.%s', $catalogName, $name)] = $message;
            }
        }

        return $result;
    }

    /**
     * @return array<string,\WebServCo\I18n\DataTransfer\MessagePattern>
     */
    private function parseMessages(LanguageInterface $language): array
    {
        $result = [];

        foreach ($this->getMessages($language) as $key => $message) {
            $messagePattern = $this->parseMessage($message);
            if (!$messagePattern instanceof MessagePattern) {
                continue;
            }

            $result[$key] = $messagePattern;
        }

        return $result;
    }

    private function parseMessage(string $message): ?MessagePattern
    {
        try {
            return $this->messagePatternParser->parse($message);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
