<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service\Check;

use Override;
use WebServCo\I18n\Contract\CatalogCheckInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\DataTransfer\CatalogProblem;

use function preg_match_all;
use function sprintf;

/**
 * No message of one language has a word that matches a list of patterns. Only the text of the messages is checked,
 * cases included, not argument names.
 *
 * The patterns of the component (`US_SPELLING_PATTERNS`) find common US spellings, for British English. They are a
 * list, not a dictionary: a review still reads for spelling.
 */
final class SpellingCheck implements CatalogCheckInterface
{
    /**
     * US spellings that British English writes otherwise: the common ones, not a dictionary.
     */
    public const array US_SPELLING_PATTERNS = [
        // -ize, -yze: synchronise, analyse. Not size, prize, seize, capsize, maize, baize: -ize in British too.
        '/\b(?!(?:(?:re|over|down|up)?si|pri|sei|capsi|mai|bai)z(?:e|es|ed|ing)\b)\w*[iy]z'
            . '(?:e|es|ed|ing|er|ers|ation|ations)\b/iu',
        // -or: colour, favourite, behaviour.
        '/\b(?:arm|behavi|cand|clam|col|endeav|fav|ferv|flav|harb|hon|hum|lab|neighb|od|parl|rig|rum|sav|savi|splend'
            . '|tum|val|vap|vig)or(?:s|ed|ing|ful|ite|ites|able|ably|er|ers|less|al|y|hood)?\b/iu',
        // -er: centre, theatre, fibre, litre.
        '/\b(?:calib|cent|fib|lit|lust|maneuv|meag|sab|somb|spect|theat)er(?:s|ed|ing)?\b/iu',
        // -og: catalogue, dialogue, analogue.
        '/\b(?:analog|catalog|dialog)(?:s|ed|ing|er|ers)?\b/iu',
        // One l before an ending: cancelled, labelled, travelling, marvellous.
        '/\b(?:cancel|channel|counsel|dial|duel|equal|fuel|jewel|label|level|marvel|model|signal|total|travel|tunnel)'
            . '(?:ed|ing|er|ers|or|ors|ous)\b/iu',
        // Words: grey, artefact, aluminium, jewellery, skilful, wilful, enrol, fulfil, instalment, defence, offence.
        '/\b(?:gray(?:s|ed|ing|ish)?|artifacts?|aluminum|jewelry|skillful(?:ly)?|willful(?:ly)?|enrolls?|enrollment'
            . '|fulfills?|fulfillment|installments?|defense|offense)\b/iu',
    ];

    /**
     * @param \WebServCo\I18n\Contract\LanguageInterface $language The language to check, eg. British English.
     * @param list<non-empty-string> $patterns Regular expressions (`preg_match_all()`) that find refused words.
     */
    public function __construct(
        private CatalogReader $catalogReader,
        private LanguageInterface $language,
        private array $patterns = self::US_SPELLING_PATTERNS,
    ) {
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem>
     */
    #[Override]
    public function check(): array
    {
        $result = [];

        foreach ($this->catalogReader->getMessagePatterns($this->language) as $key => $messagePattern) {
            foreach ($this->findWords($messagePattern->text) as $word) {
                $result[] = new CatalogProblem(
                    self::class,
                    $this->language->getCode(),
                    $key,
                    sprintf('Refused spelling: "%s".', $word),
                );
            }
        }

        return $result;
    }

    /**
     * @return list<string> The refused words of the text, by pattern.
     */
    private function findWords(string $text): array
    {
        $result = [];

        foreach ($this->patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            $result = [...$result, ...$matches[0] ?? []];
        }

        return $result;
    }
}
