<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service;

use InvalidArgumentException;
use MessageFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WebServCo\I18n\DataTransfer\ComplexArgument;
use WebServCo\I18n\DataTransfer\MessagePattern;
use WebServCo\I18n\Service\MessagePatternParser;

use function sprintf;

#[CoversClass(ComplexArgument::class)]
#[CoversClass(MessagePattern::class)]
#[CoversClass(MessagePatternParser::class)]
final class MessagePatternParserTest extends TestCase
{
    public function testArgumentsHaveTheirTypes(): void
    {
        $pattern = $this->parse('{name} has {count, number} records since {when, date, short}.');

        self::assertSame(['count' => ['number'], 'name' => ['none'], 'when' => ['date']], $pattern->arguments);
        self::assertSame([], $pattern->complexArguments);
        self::assertSame('  has   records since  .', $pattern->text);
    }

    public function testAPluralHasItsSelectorsAndTheNumberSignIsNotText(): void
    {
        $pattern = $this->parse('{count, plural, =0 {No records} one {# record} other {# records}}');

        self::assertSame(['count' => ['plural']], $pattern->arguments);
        self::assertEquals(
            [new ComplexArgument('count', 'plural', ['=0', 'one', 'other'])],
            $pattern->complexArguments,
        );
        self::assertSame(' No records   record   records ', $pattern->text);
    }

    public function testNestedArgumentsAreIncluded(): void
    {
        $pattern = $this->parse(
            '{count, plural, one {{name} has # record} other {{name, select, a {A} other {B}} has # records}}',
        );

        self::assertSame(['count' => ['plural'], 'name' => ['none', 'select']], $pattern->arguments);
        self::assertEquals(
            [
                new ComplexArgument('name', 'select', ['a', 'other']),
                new ComplexArgument('count', 'plural', ['one', 'other']),
            ],
            $pattern->complexArguments,
        );
    }

    public function testAPluralOffsetIsNotASelector(): void
    {
        foreach (['offset:1', 'offset: 1'] as $offset) {
            $pattern = $this->parse(sprintf('{n, plural, %s =0 {Nobody} other {You and # others}}', $offset));

            self::assertEquals([new ComplexArgument('n', 'plural', ['=0', 'other'])], $pattern->complexArguments);
        }
    }

    public function testATypeIsLowercase(): void
    {
        $pattern = $this->parse('{count, PLURAL, one {x} other {y}}');

        self::assertSame(['count' => ['plural']], $pattern->arguments);
        self::assertEquals([new ComplexArgument('count', 'plural', ['one', 'other'])], $pattern->complexArguments);
    }

    public function testASelectorTwiceIsListedTwice(): void
    {
        self::assertEquals(
            [new ComplexArgument('n', 'plural', ['one', 'one', 'other'])],
            $this->parse('{n, plural, one {a} one {b} other {c}}')->complexArguments,
        );
    }

    public function testAStyleIsSkippedWithItsQuotesAndBraces(): void
    {
        $pattern = $this->parse("{d, date, 'x}'yyyy} {n, number, ::currency/EUR} {c, choice, 0#none|1#{c} item}");

        self::assertSame(['c' => ['choice'], 'd' => ['date'], 'n' => ['number']], $pattern->arguments);
        self::assertSame('     ', $pattern->text);
    }

    public function testAQuotedNumberSignInAPluralIsText(): void
    {
        self::assertSame(' # is   ', $this->parse("{n, plural, other {'#' is #}}")->text);
    }

    public function testANumberSignInASelectInsideAPluralIsText(): void
    {
        self::assertSame('  # x  ', $this->parse('{n, plural, other {{g, select, other {# x}}}}')->text);
    }

    /**
     * Without arguments, the text is what ICU prints.
     */
    #[DataProvider('provideTextWithoutArguments')]
    public function testTheTextIsWhatIcuPrints(string $pattern): void
    {
        self::assertSame(new MessageFormatter('en', $pattern)->format([]), $this->parse($pattern)->text);
    }

    public function testEachParseStartsAfresh(): void
    {
        $parser = new MessagePatternParser();
        $parser->parse('{a, select, x {X} other {Y}} text');

        $pattern = $parser->parse('{b}');

        self::assertSame(['b' => ['none']], $pattern->arguments);
        self::assertSame([], $pattern->complexArguments);
        self::assertSame(' ', $pattern->text);
    }

    #[DataProvider('provideInvalidPattern')]
    public function testAnInvalidPatternThrows(string $pattern, string $problem): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs(sprintf('Invalid ICU message, %s: "%s".', $problem, $pattern));

        $this->parse($pattern);
    }

    /**
     * @return iterable<string,array{0: string}>
     */
    public static function provideTextWithoutArguments(): iterable
    {
        yield 'plain' => ['Hello'];
        yield 'apostrophe' => ["Don't"];
        yield 'apostrophes' => ["a'b'c"];
        yield 'double apostrophe' => ["It''s"];
        yield 'double apostrophe first' => ["'' first"];
        yield 'quoted braces' => ["'{' and '}'"];
        yield 'quoted text' => ["'{not an argument}'"];
        yield 'quoted to the end' => ["'{unterminated"];
        yield 'double apostrophe in quoted text' => ["'{it''s}'"];
        yield 'number sign outside a plural' => ["'#' and #"];
    }

    /**
     * @return iterable<string,array{0: string, 1: string}>
     */
    public static function provideInvalidPattern(): iterable
    {
        yield 'no name' => ['{}', 'argument without a name at offset 1'];
        yield 'no closing brace' => ['{name', '"}" expected at offset 5'];
        yield 'no opening brace' => ['a } b', '"}" without "{" at offset 2'];
        yield 'no cases' => ['{n, plural,}', 'argument without cases at offset 11'];
        yield 'no selector' => ['{n, plural, {x} other {y}}', 'case without a selector at offset 12'];
        yield 'case without braces' => ['{n, plural, one x}', '"{" expected at offset 16'];
        yield 'style without closing brace' => ['{d, date, short', '"}" expected at offset 15'];
        yield 'style quote without end' => [
            "{d, date, 'unterminated}",
            '"}" expected at offset 10',
        ];
    }

    private function parse(string $pattern): MessagePattern
    {
        return new MessagePatternParser()->parse($pattern);
    }
}
