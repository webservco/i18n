<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service;

use InvalidArgumentException;
use WebServCo\I18n\DataTransfer\ComplexArgument;
use WebServCo\I18n\DataTransfer\MessagePattern;

use function array_keys;
use function in_array;
use function ksort;
use function preg_match;
use function sort;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtolower;

/**
 * Reads the structure of an ICU message: its arguments, the cases of its plural and select arguments, and its literal
 * text. For the catalog checks (`Service\Check\`); `MessageFormatter` formats.
 *
 * ICU syntax (https://unicode-org.github.io/icu/userguide/format_parse/messages/), with ICU's default apostrophe
 * mode: `''` is an apostrophe, and an apostrophe starts quoted text only before `{` or `}`, or `#` in a plural case.
 * The style of other arguments (`{count, number, integer}`) is skipped. ASCII whitespace only.
 * Not reentrant: it keeps the state of the message it parses.
 */
final class MessagePatternParser
{
    /**
     * The type of an argument without one: `{name}`.
     */
    public const string TYPE_NONE = 'none';

    /**
     * Argument types whose style is a list of cases (`selector {message}`), and those of them where `#` is the number.
     */
    private const array CASE_TYPES = ['plural', 'select', 'selectordinal'];
    private const array PLURAL_TYPES = ['plural', 'selectordinal'];

    /**
     * Characters that end a name, a type or a selector.
     */
    private const array TOKEN_ENDS = ['', ' ', "\t", "\n", "\r", ',', '{', '}'];

    private const array WHITESPACE = [' ', "\t", "\n", "\r"];

    /**
     * The style of an argument other than plural and select (`skipStyle()`): quoted text, other characters except
     * braces, and balanced braces.
     */
    private const string STYLE_PATTERN = "/\\G((?:'[^']*'|[^'{}]++|\\{(?1)\\})*)/";

    /**
     * Argument name => type => true.
     *
     * @var array<string,array<string,true>>
     */
    private array $arguments = [];

    /**
     * @var list<\WebServCo\I18n\DataTransfer\ComplexArgument>
     */
    private array $complexArguments = [];

    private string $pattern = '';

    private int $position = 0;

    private string $text = '';

    public function parse(string $pattern): MessagePattern
    {
        $this->pattern = $pattern;
        $this->position = 0;
        $this->arguments = [];
        $this->complexArguments = [];
        $this->text = '';

        $this->readMessage(false);
        if ($this->position < strlen($this->pattern)) {
            throw $this->createException('"}" without "{"');
        }

        return new MessagePattern($this->getArguments(), $this->complexArguments, $this->text);
    }

    private function createException(string $problem): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf('Invalid ICU message, %s at offset %d: "%s".', $problem, $this->position, $this->pattern),
        );
    }

    private function expect(string $char): void
    {
        if ($this->peek() !== $char) {
            throw $this->createException(sprintf('"%s" expected', $char));
        }

        $this->position += 1;
    }

    /**
     * @return array<string,list<string>>
     */
    private function getArguments(): array
    {
        $result = [];

        foreach ($this->arguments as $name => $types) {
            $types = array_keys($types);
            sort($types);
            $result[$name] = $types;
        }
        ksort($result);

        return $result;
    }

    /**
     * The character at the position, and the position moves past it.
     */
    private function next(): string
    {
        $char = $this->peek();
        $this->position += 1;

        return $char;
    }

    /**
     * The character at the position; empty at the end.
     */
    private function peek(): string
    {
        return $this->pattern[$this->position] ?? '';
    }

    private function peekAfterWhitespace(): string
    {
        while (in_array($this->peek(), self::WHITESPACE, true)) {
            $this->position += 1;
        }

        return $this->peek();
    }

    /**
     * An apostrophe in a message (the position is after it): an apostrophe, or the start of quoted text.
     */
    private function readApostrophe(bool $isPluralCase): void
    {
        $char = $this->peek();
        if ($char === "'") {
            $this->text .= $this->next();

            return;
        }

        if ($char === '{' || $char === '}' || ($char === '#' && $isPluralCase)) {
            $this->readQuotedText();

            return;
        }

        $this->text .= "'";
    }

    /**
     * An argument (the position is after its `{`), up to and including its `}`.
     */
    private function readArgument(): void
    {
        $name = $this->readToken();
        if ($name === '') {
            throw $this->createException('argument without a name');
        }

        $type = self::TYPE_NONE;
        if ($this->peekAfterWhitespace() === ',') {
            $this->position += 1;
            $type = strtolower($this->readToken());
            $this->readArgumentStyle($name, $type);
        }

        $this->expect('}');
        $this->arguments[$name][$type] = true;
        $this->text .= ' ';
    }

    /**
     * The style of an argument, if any (the position is after its type), up to its `}` (excluded).
     */
    private function readArgumentStyle(string $name, string $type): void
    {
        if ($this->peekAfterWhitespace() !== ',') {
            return;
        }

        $this->position += 1;
        if (in_array($type, self::CASE_TYPES, true)) {
            $this->readCases($name, $type);

            return;
        }

        $this->skipStyle();
    }

    /**
     * The message of a case (the position is after its selector), with its braces.
     */
    private function readCase(string $type): void
    {
        $this->peekAfterWhitespace();
        $this->expect('{');
        $this->text .= ' ';
        $this->readMessage(in_array($type, self::PLURAL_TYPES, true));
        $this->expect('}');
    }

    /**
     * The cases of a plural, selectordinal or select argument, up to its `}` (excluded).
     */
    private function readCases(string $name, string $type): void
    {
        $selectors = [];

        while ($this->peekAfterWhitespace() !== '}') {
            $selector = $this->readSelector($type);
            if ($selector === null) {
                continue;
            }

            $this->readCase($type);
            $selectors[] = $selector;
        }

        if ($selectors === []) {
            throw $this->createException('argument without cases');
        }
        $this->complexArguments[] = new ComplexArgument($name, $type, $selectors);
    }

    /**
     * A message: text and arguments, up to the `}` that ends a case (excluded) or the end of the pattern.
     */
    private function readMessage(bool $isPluralCase): void
    {
        while (!in_array($this->peek(), ['', '}'], true)) {
            $this->readMessagePart($isPluralCase);
        }
    }

    /**
     * A character of a message, or the argument or the quoted text it starts.
     */
    private function readMessagePart(bool $isPluralCase): void
    {
        $char = $this->next();
        if ($char === '{') {
            $this->readArgument();

            return;
        }
        if ($char === "'") {
            $this->readApostrophe($isPluralCase);

            return;
        }

        $this->text .= $char === '#' && $isPluralCase
            ? ' '
            : $char;
    }

    /**
     * Quoted text (the position is after the apostrophe that starts it), up to and including the apostrophe that
     * ends it, or to the end of the pattern. `''` inside is an apostrophe.
     */
    private function readQuotedText(): void
    {
        while ($this->peek() !== '') {
            $char = $this->next();
            if ($char !== "'") {
                $this->text .= $char;

                continue;
            }
            if ($this->peek() !== "'") {
                return;
            }

            $this->text .= $this->next();
        }
    }

    /**
     * The selector of a case; null for the offset of a plural (`offset:1`, or `offset:` and the value).
     */
    private function readSelector(string $type): ?string
    {
        $token = $this->readToken();
        if ($token === '') {
            throw $this->createException('case without a selector');
        }
        if ($type !== 'plural' || !str_starts_with($token, 'offset:')) {
            return $token;
        }

        if ($token === 'offset:') {
            $this->readToken();
        }

        return null;
    }

    /**
     * A name, a type or a selector, after any whitespace.
     */
    private function readToken(): string
    {
        $this->peekAfterWhitespace();
        $result = '';

        while (!in_array($this->peek(), self::TOKEN_ENDS, true)) {
            $result .= $this->next();
        }

        return $result;
    }

    /**
     * The style of an argument other than plural and select, up to its `}` (excluded): braces are balanced, and an
     * apostrophe quotes up to the next one. A style without its `}` stops early, and the argument's `}` is missing.
     */
    private function skipStyle(): void
    {
        // The pattern matches the empty string, so there is no match only on a PCRE error: nothing is skipped.
        preg_match(self::STYLE_PATTERN, $this->pattern, $matches, 0, $this->position);

        $this->position += strlen($matches[0] ?? '');
    }
}
