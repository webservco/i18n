<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\View;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\LocalizedTestView;
use Tests\Unit\Assets\TestLanguage;
use WebServCo\I18n\Container\LocalizationContainer;
use WebServCo\I18n\Contract\FormatterInterface;
use WebServCo\I18n\Contract\TranslatorInterface;
use WebServCo\I18n\View\AbstractLocalizedView;

use function json_encode;

use const JSON_THROW_ON_ERROR;

#[CoversClass(AbstractLocalizedView::class)]
#[UsesClass(LocalizationContainer::class)]
final class AbstractLocalizedViewTest extends TestCase
{
    public function testTextIsTranslatedAndEscaped(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())->method('translate')
            ->with('layout.footer.contact', ['name' => '<b>'])
            ->willReturn('Kontakt & <b>');

        self::assertSame(
            'Kontakt &amp; &lt;b&gt;',
            $this->createView($translator, self::createStub(FormatterInterface::class))
                ->text('layout.footer.contact', ['name' => '<b>']),
        );
    }

    public function testTheLanguageCodeIsTheLanguageOfTheTranslator(): void
    {
        $translator = self::createStub(TranslatorInterface::class);
        $translator->method('getLanguage')->willReturn(TestLanguage::Hungarian);

        self::assertSame(
            'hu',
            $this->createView($translator, self::createStub(FormatterInterface::class))->languageCode(),
        );
    }

    public function testDatesAndTimesComeFromTheFormatterEscaped(): void
    {
        $dateTime = new DateTimeImmutable('2026-11-05 21:07:00');
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('formatDate')->with($dateTime)->willReturn('5 <nov>');
        $formatter->expects(self::once())->method('formatDateTime')->with($dateTime)->willReturn('5 nov, 21:07');
        $formatter->expects(self::once())->method('formatTime')->with($dateTime)->willReturn('21:07');
        $view = $this->createView(self::createStub(TranslatorInterface::class), $formatter);

        self::assertSame('5 &lt;nov&gt;', $view->date($dateTime));
        self::assertSame('5 nov, 21:07', $view->dateTime($dateTime));
        self::assertSame('21:07', $view->time($dateTime));
    }

    public function testNumbersComeFromTheFormatter(): void
    {
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('formatNumber')->with(1234.5, 2)->willReturn('1.234,50');

        self::assertSame(
            '1.234,50',
            $this->createView(self::createStub(TranslatorInterface::class), $formatter)->number(1234.5, 2),
        );
    }

    public function testTheLocalizationServicesStayOutOfTheJsonOutput(): void
    {
        $json = json_encode(
            $this->createView(
                self::createStub(TranslatorInterface::class),
                self::createStub(FormatterInterface::class),
            ),
            JSON_THROW_ON_ERROR,
        );

        self::assertSame('{"title":"Title"}', $json);
    }

    private function createView(TranslatorInterface $translator, FormatterInterface $formatter): LocalizedTestView
    {
        return new LocalizedTestView(new LocalizationContainer($formatter, $translator), 'Title');
    }
}
