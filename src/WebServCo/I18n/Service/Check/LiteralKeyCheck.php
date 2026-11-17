<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use Override;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;
use WebServCo\I18n\Contract\CatalogCheckInterface;
use WebServCo\I18n\DataTransfer\CatalogProblem;

use function array_filter;
use function array_values;
use function explode;
use function file_get_contents;
use function in_array;
use function is_array;
use function preg_match;
use function rtrim;
use function sort;
use function sprintf;
use function strlen;
use function substr;
use function token_get_all;

use const DIRECTORY_SEPARATOR;
use const T_CONSTANT_ENCAPSED_STRING;

/**
 * Every literal translation key in the code exists in the default language, so that a typo fails a check instead of
 * a page.
 *
 * A literal key is a PHP string (not inline HTML, not a comment) in a `.php` file of the source directories that looks
 * like a key and starts with the name of a catalog of the default language: `'layout.footer.contact'`. A key built at
 * runtime (`'log.' . $name`) is not seen, so write keys in full.
 */
final class LiteralKeyCheck implements CatalogCheckInterface
{
    /**
     * `{catalog}.{name}`: the catalog name, then the message name.
     */
    public const string KEY_PATTERN = '/^([a-z][a-z0-9]*(?:_[a-z0-9]+)*)\.([a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*)$/';

    private string $projectPath;

    /**
     * @param string $projectPath The directory the source directories are in.
     * @param list<string> $sourceDirectories Relative to the project path, eg.
     *     `['config', 'resources/templates', 'src']`.
     */
    public function __construct(
        private CatalogReader $catalogReader,
        string $projectPath,
        private array $sourceDirectories,
    ) {
        $this->projectPath = rtrim($projectPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem> A key the default language lacks, with the first file
     *     that has it; one problem when no key is found at all (wrong directories).
     */
    #[Override]
    public function check(): array
    {
        $defaultLanguage = $this->catalogReader->getLanguageList()->getDefaultLanguage();
        $messages = $this->catalogReader->getMessages($defaultLanguage);
        $keys = $this->findLiteralKeys($this->catalogReader->getCatalogNames($defaultLanguage));
        if ($keys === []) {
            return [new CatalogProblem(self::class, null, null, 'No literal key in the source directories.')];
        }

        $result = [];
        foreach ($keys as $key => $filePath) {
            if (isset($messages[$key])) {
                continue;
            }

            $result[] = new CatalogProblem(
                self::class,
                $defaultLanguage->getCode(),
                $key,
                sprintf('Not in the default language (%s).', $filePath),
            );
        }

        return $result;
    }

    /**
     * @param list<string> $catalogNames
     * @return array<string,string> Key => the first file that has it (relative path).
     */
    private function findLiteralKeys(array $catalogNames): array
    {
        $result = [];

        foreach ($this->getFilePaths() as $filePath) {
            foreach ($this->findFileKeys($filePath, $catalogNames) as $key) {
                $result[$key] ??= substr($filePath, strlen($this->projectPath));
            }
        }

        return $result;
    }

    /**
     * @param list<string> $catalogNames
     * @return list<string>
     */
    private function findFileKeys(string $filePath, array $catalogNames): array
    {
        return array_values(
            array_filter(
                $this->getStringLiterals($filePath),
                function (string $value) use ($catalogNames): bool {
                    return $this->isKey($value, $catalogNames);
                },
            ),
        );
    }

    /**
     * @return list<string> The `.php` files of a directory and its subdirectories, sorted.
     */
    private function getDirectoryFilePaths(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectPath . $directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        $result = [];

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $result[] = $file->getPathname();
        }
        sort($result);

        return $result;
    }

    /**
     * @return list<string>
     */
    private function getFilePaths(): array
    {
        $result = [];

        foreach ($this->sourceDirectories as $directory) {
            $result = [...$result, ...$this->getDirectoryFilePaths($directory)];
        }

        return $result;
    }

    /**
     * The PHP strings of a file, without their quotes: a key has no quotes or backslashes to unescape.
     *
     * @return list<string>
     */
    private function getStringLiterals(string $filePath): array
    {
        $code = file_get_contents($filePath);
        if ($code === false) {
            throw new UnexpectedValueException(sprintf('Error reading "%s".', $filePath));
        }

        $result = [];
        foreach (token_get_all($code) as $token) {
            if (!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $result[] = substr($token[1], 1, -1);
        }

        return $result;
    }

    /**
     * A string that looks like a key and starts with the name of a catalog.
     *
     * @param list<string> $catalogNames
     */
    private function isKey(string $value, array $catalogNames): bool
    {
        return preg_match(self::KEY_PATTERN, $value) === 1
            && in_array(explode('.', $value, 2)[0], $catalogNames, true);
    }
}
