<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use Override;
use WebServCo\I18n\Contract\CatalogCheckInterface;
use WebServCo\I18n\DataTransfer\CatalogProblem;

use function sprintf;
use function str_contains;

/**
 * No message has a letter that only looks right in its language: the wrong letter is in the font, so nobody sees it.
 */
final class LookalikeLetterCheck implements CatalogCheckInterface
{
    /**
     * Per language code: letters that only look right (wrong => right). Hungarian ő and ű have a double acute accent,
     * not a circumflex or a tilde. Romanian ș and ț have a comma below, not a cedilla, and ă a breve, not a tilde.
     */
    public const array LOOKALIKE_LETTERS = [
        'hu' => ['Ô' => 'Ő', 'Õ' => 'Ő', 'Û' => 'Ű', 'ô' => 'ő', 'õ' => 'ő', 'û' => 'ű'],
        'ro' => ['Ã' => 'Ă', 'Ş' => 'Ș', 'Ţ' => 'Ț', 'ã' => 'ă', 'ş' => 'ș', 'ţ' => 'ț'],
    ];

    /**
     * @param array<string,array<string,string>> $lookalikeLetters Language code => wrong letter => right letter.
     *     Other languages: `[...LookalikeLetterCheck::LOOKALIKE_LETTERS, 'xx' => [...]]`.
     */
    public function __construct(
        private CatalogReader $catalogReader,
        private array $lookalikeLetters = self::LOOKALIKE_LETTERS,
    ) {
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    #[Override]
    public function check(): array
    {
        $result = [];

        foreach ($this->catalogReader->getLanguageList()->getLanguages() as $language) {
            $letters = $this->lookalikeLetters[$language->getCode()] ?? [];

            foreach ($this->catalogReader->getMessages($language) as $key => $message) {
                $result = [...$result, ...$this->checkMessage($language->getCode(), $key, $message, $letters)];
            }
        }

        return $result;
    }

    /**
     * @param array<string,string> $letters Wrong letter => right letter.
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    private function checkMessage(string $languageCode, string $key, string $message, array $letters): array
    {
        $result = [];

        foreach ($letters as $wrong => $right) {
            if (!str_contains($message, $wrong)) {
                continue;
            }

            $result[] = new CatalogProblem(
                self::class,
                $languageCode,
                $key,
                sprintf('"%s" instead of "%s".', $wrong, $right),
            );
        }

        return $result;
    }
}
