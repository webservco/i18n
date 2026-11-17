<?php

declare(strict_types=1);

namespace WebServCo\I18n\View;

use WebServCo\View\Contract\ViewInterface;
use WebServCo\View\View\AbstractView;

/**
 * An item of the language switch (`LanguageLinkViewFactory`).
 */
final class LanguageLinkView extends AbstractView implements ViewInterface
{
    public function __construct(
        // Language code: `lang` and `hreflang` attributes.
        public readonly string $code,
        // The language of the page.
        public readonly bool $isCurrent,
        // In the language itself.
        public readonly string $name,
        // Relative: the current page with `?lang={code}`.
        public readonly string $url,
    ) {
    }
}
