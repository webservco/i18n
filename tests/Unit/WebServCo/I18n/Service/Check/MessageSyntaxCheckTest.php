<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use WebServCo\I18n\DataTransfer\CatalogProblem;
use WebServCo\I18n\DataTransfer\ComplexArgument;
use WebServCo\I18n\DataTransfer\MessagePattern;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\Check\MessageSyntaxCheck;
use WebServCo\I18n\Service\LanguageList;
use WebServCo\I18n\Service\MessagePatternParser;

use function ini_get;
use function ini_set;

#[CoversClass(MessageSyntaxCheck::class)]
#[UsesClass(CatalogLoader::class)]
#[UsesClass(CatalogProblem::class)]
#[UsesClass(CatalogReader::class)]
#[UsesClass(ComplexArgument::class)]
#[UsesClass(LanguageList::class)]
#[UsesClass(MessagePattern::class)]
#[UsesClass(MessagePatternParser::class)]
final class MessageSyntaxCheckTest extends AbstractCatalogCheckTestCase
{
    public function testTheValidSetHasNoProblem(): void
    {
        self::assertSame([], new MessageSyntaxCheck($this->createCatalogReader('Valid'))->check());
    }

    public function testInvalidNamesWhitespaceAndInvalidMessagesAreReported(): void
    {
        self::assertEquals(
            $this->createExpectedProblems('msgfmt_create: message formatter creation failed: U_UNMATCHED_BRACES'),
            new MessageSyntaxCheck($this->createCatalogReader('MessageSyntax'))->check(),
        );
    }

    /**
     * With `intl.use_exceptions`, ICU throws instead of returning null: the check still reports.
     */
    public function testAnIcuErrorIsReportedWithIntlExceptionsToo(): void
    {
        $useExceptions = (string) ini_get('intl.use_exceptions');
        ini_set('intl.use_exceptions', '1');

        try {
            $problems = new MessageSyntaxCheck($this->createCatalogReader('MessageSyntax'))->check();
        } finally {
            ini_set('intl.use_exceptions', $useExceptions);
        }

        self::assertEquals(
            $this->createExpectedProblems('msgfmt_create: message formatter creation failed'),
            $problems,
        );
    }

    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem> The problems of the broken set.
     */
    private function createExpectedProblems(string $icuError): array
    {
        return [
            $this->createProblem('de', 'page.title', 'Surrounding whitespace.'),
            $this->createProblem('en', null, 'Invalid catalog name: "Bad-Name".'),
            $this->createProblem('en', 'page.icu_error', 'Invalid ICU message: ' . $icuError . '.'),
            $this->createProblem(
                'en',
                'page.offset',
                'Valid ICU, but the other checks can not read it: Invalid ICU message, case without a selector '
                . 'at offset 30: "{count, plural, offset:1other {#}}".',
            ),
            $this->createProblem('en', 'page.padded', 'Surrounding whitespace.'),
            $this->createProblem('en', 'page.Title', 'Invalid message name.'),
        ];
    }

    private function createProblem(?string $languageCode, ?string $key, string $description): CatalogProblem
    {
        return new CatalogProblem(MessageSyntaxCheck::class, $languageCode, $key, $description);
    }
}
