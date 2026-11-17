<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use IntlException;
use InvalidArgumentException;
use Override;
use WebServCo\I18n\Contract\CatalogCheckInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\MessagePatternParser;

use function intl_get_error_message;
use function msgfmt_create;
use function preg_match;
use function sprintf;
use function trim;

/**
 * Every catalog and message has a valid name, and every message is valid ICU MessageFormat, without surrounding
 * whitespace, that the other checks can read.
 */
final class MessageSyntaxCheck implements CatalogCheckInterface
{
    /**
     * Message names: lowercase words joined by underscores, in dot-separated groups (eg. `footer.contact`). Checked on
     * the key, whose catalog name is such a group too.
     */
    public const string MESSAGE_NAME_PATTERN = '/^[a-z][a-z0-9]*(_[a-z0-9]+)*(\.[a-z][a-z0-9]*(_[a-z0-9]+)*)*$/';

    private MessagePatternParser $messagePatternParser;

    public function __construct(private CatalogReader $catalogReader)
    {
        $this->messagePatternParser = new MessagePatternParser();
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    #[Override]
    public function check(): array
    {
        $result = [];

        foreach ($this->catalogReader->getLanguageList()->getLanguages() as $language) {
            $result = [...$result, ...$this->checkCatalogNames($language), ...$this->checkMessages($language)];
        }

        return $result;
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkCatalogNames(LanguageInterface $language): array
    {
        $result = [];

        foreach ($this->catalogReader->getCatalogNames($language) as $catalogName) {
            if (preg_match(CatalogLoader::CATALOG_NAME_PATTERN, $catalogName) === 1) {
                continue;
            }

            $result[] = new CatalogProblem(
                self::class,
                $language->getCode(),
                null,
                sprintf('Invalid catalog name: "%s".', $catalogName),
            );
        }

        return $result;
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkMessages(LanguageInterface $language): array
    {
        $result = [];

        foreach ($this->catalogReader->getMessages($language) as $key => $message) {
            foreach ($this->findMessageProblems($language, $key, $message) as $description) {
                $result[] = new CatalogProblem(self::class, $language->getCode(), $key, $description);
            }
        }

        return $result;
    }

    /**
     * @return list<string> Descriptions.
     */
    private function findMessageProblems(LanguageInterface $language, string $key, string $message): array
    {
        $result = [];

        if (preg_match(self::MESSAGE_NAME_PATTERN, $key) !== 1) {
            $result[] = 'Invalid message name.';
        }
        if (trim($message) !== $message) {
            $result[] = 'Surrounding whitespace.';
        }

        $syntaxProblem = $this->findSyntaxProblem($language, $message);
        if ($syntaxProblem !== null) {
            $result[] = $syntaxProblem;
        }

        return $result;
    }

    /**
     * The ICU error of the message, if any (errors only: `{count, number}` gives U_USING_DEFAULT_WARNING).
     */
    private function findIcuError(LanguageInterface $language, string $message): ?string
    {
        try {
            $messageFormatter = msgfmt_create($language->getLocale(), $message);
        } catch (IntlException $exception) {
            // With `intl.use_exceptions`.
            return $exception->getMessage();
        }

        return $messageFormatter === null
            ? intl_get_error_message()
            : null;
    }

    /**
     * An ICU error, or a valid message the other checks can't read (they leave it out).
     */
    private function findSyntaxProblem(LanguageInterface $language, string $message): ?string
    {
        $icuError = $this->findIcuError($language, $message);
        if ($icuError !== null) {
            return sprintf('Invalid ICU message: %s.', $icuError);
        }

        try {
            $this->messagePatternParser->parse($message);
        } catch (InvalidArgumentException $exception) {
            return sprintf('Valid ICU, but the other checks can not read it: %s', $exception->getMessage());
        }

        return null;
    }
}
